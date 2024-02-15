<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$settings = require __DIR__.'/../../cfg/settings.php';
$PrepBofId = $settings['settings']['PrepBofId'];

$app->get('/votes/list', function (Request $request, Response $response, array $args) {
    $sql_get_votes = '
      SELECT participant.id AS participant_id, participant.name AS participant,
            leader, participant AS vote,
            workshop.id AS workshop_id, workshop.name AS workshop
      FROM workshop_participant
      JOIN participant ON participant_id = participant.id
      JOIN workshop ON workshop_id = workshop.id
      WHERE participant > 0
    ';
    $sth = $this->db->prepare($sql_get_votes);
    $sth->execute();
    $votes = $sth->fetchAll();

    return $this->view->render($response, 'votelist.html', [
        'votes' => $votes,
    ]);

})->setName("voteslist");

$app->get('/votes/add', function (Request $request, Response $response, array $args) {
    $sql_get_workshops = 'SELECT * FROM workshop';
    $sth = $this->db->prepare($sql_get_workshops);
    $sth->execute();
    $workshop = $sth->fetchAll();

    return $this->view->render($response, 'addvote.html', [
        'workshops' => $workshop
    ]);
})->setName("votesaddget");

$app->post('/votes/add', function (Request $request, Response $response, array $args) {
    global $PrepBofId, $app;
    $data = $request->getParsedBody();

    $setting_leader = 1;
    if (!array_key_exists('leader', $data)) {
       $data['leader'] = 0;
       $setting_leader = 0;
    }

    // check user
    $userid = $request->getAttribute('userid');
    if($userid === NULL)
        return $response->withRedirect($this->router->pathFor('home'), 302);

    $dbo = $app->getContainer()->get('ICCM\BOF\DBO');
    $stage =$dbo->getStage();
    if ($stage != 'voting')
       return $response->withRedirect($this->router->pathFor('home'), 302);

    // turn full votes for Prep BOF into quarter votes. we don't want to waste your full vote!
    if ($data['workshopid'] == $PrepBofId || $data['workshopid'] == 124) {
        $data['vote'] = 0.25;
    }

    // check allowed full-votes (not more than 3)
    $sql_get_totalvotes = 'SELECT COUNT(*) FROM workshop_participant WHERE participant = 1 AND participant_id = :uid';
    $sth = $this->db->prepare($sql_get_totalvotes);
    $sth->execute(['uid' => $userid]);
    $totalvotes = $sth->fetch();
    if($totalvotes[0] >= 3 && $data['vote'] >= 1)
        return $response->withRedirect($this->router->pathFor('topics'), 302);

    // find existing vote entries from this user on this workshop
    $sql_get_votes = '
        SELECT * FROM workshop_participant
        WHERE workshop_id = :wid AND participant_id = :uid
    ';
    $sth = $this->db->prepare($sql_get_votes);
    $sth->execute(['wid' => $data['workshopid'], 'uid' => $userid]);
    $votes = $sth->fetchAll();

    if(count($votes) == 0) {
        // create new vote
        $sql_vote = 'INSERT INTO workshop_participant 
                         (workshop_id, participant_id, participant, leader) VALUES (:wid, :uid, :vote, :leader)';
    } else {
        // update vote
        $sql_vote = 'UPDATE workshop_participant SET participant = :vote, leader = :leader
                     WHERE workshop_id = :wid AND participant_id = :uid';
    }
    $this->db->beginTransaction();
    $sth = $this->db->prepare($sql_vote);
    $sth->execute([
        'wid' => $data['workshopid'],
        'uid' => $userid,
        'vote' => $data['vote'],
        'leader' => $data['leader']
    ]);
    $this->db->commit();
    $param = $data['vote'] == '0' ? [] : ['voted' => 1];
    if ($setting_leader) {
        $param['leader'] = $data['leader'];
    }
    return $response->withRedirect($this->router->pathFor('topics', [], $param), 302);
})->setName("votesaddpost");;

?>
