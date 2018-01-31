<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/votes/list', function (Request $request, Response $response, array $args) {
    $sql_get_votes = '
      SELECT participant.id AS participant_id, participant.name AS participant,
            leader, participant AS vote,
            workshop.id AS workshop_id, workshop.name AS workshop
      FROM workshop_participant
      JOIN participant ON participant_id = participant.id
      JOIN workshop ON workshop_id = workshop.id';
    $sth = $this->db->prepare($sql_get_votes);
    $sth->execute();
    $votes = $sth->fetchAll();

    return $this->view->render($response, 'votelist.html', [
        'votes' => $votes,
    ]);

});

$app->get('/votes/add', function (Request $request, Response $response, array $args) {
    $sql_get_workshops = 'SELECT * FROM workshop';
    $sth = $this->db->prepare($sql_get_workshops);
    $sth->execute();
    $workshop = $sth->fetchAll();

    return $this->view->render($response, 'addvote.html', [
        'workshops' => $workshop
    ]);
});

$app->post('/votes/add', function (Request $request, Response $response, array $args) {
    $data = $request->getParsedBody();
    $userid = $request->getAttribute('userid');
    if($userid === NULL)
        return $response->withStatus(401);

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
                         (workshop_id, participant_id, participant) VALUES (:wid, :uid, :vote)';
    } else {
        // update vote
        $sql_vote = 'UPDATE workshop_participant SET participant = :vote
                     WHERE workshop_id = :wid AND participant_id = :uid';
    }
    $this->db->beginTransaction();
    $sth = $this->db->prepare($sql_vote);
    $sth->execute([
        'wid' => $data['workshopid'],
        'uid' => $userid,
        'vote' => $data['vote']
    ]);
    $this->db->commit();

    $response->getBody()->write('You voted: ' . $data['vote'] . ' for workshopid ' . $data['workshopid']);
    return $response;
});

?>
