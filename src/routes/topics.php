<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/* 
 * gets topics list and generate the topics phase.
 * depending on the stage we're in we see topics and will be able to nominate a new one
 * or the possibility to vote for topics
 * in the last stage the list of selected topics will be there and location and time slot
 * 
 * 'stage' can be: 'nominating', 'voting'
 * System can be locked down with variable 'locked'
 * 
 * voting and nominating stages are configured in the config table by timestamps
 * locked is automatically when out of periods of voting and nominating
 * TODO: config item for locked False/True
 * 
 */
function float_eq($a, $b) {
    return abs(abs($a) - abs($b)) < 0.000001;
}
$app->get('/topics', function (Request $request, Response $response, array $args) {
    $userid = $request->getAttribute('userid');

    $sql = "SELECT *, '' as leader
            FROM `workshop`";
    $query=$this->db->prepare($sql);
    $query->execute();
    $bofs = $query->fetchAll();

    $sql = 'SELECT participant.name, workshop_id
            FROM workshop_participant
            JOIN participant ON workshop_participant.participant_id = participant.id
            WHERE workshop_participant.leader = 1';
    $query=$this->db->prepare($sql);
    $param = array ();
    $query->execute($param);
    while ($row=$query->fetch(PDO::FETCH_OBJ)) {
        foreach($bofs as &$bof) {
            if($bof['id'] === $row->workshop_id) {
                if (strlen($bof['leader']) > 0) {
                    $bof['leader'] .= ', ';
                }
                $bof['leader'] .= $row->name;
            }
        }
    }

    $sql = 'SELECT workshop_id, participant
            FROM `workshop_participant`
            WHERE participant_id = :uid';
    $query = $this->db->prepare($sql);
    $query->execute(['uid' => $userid]);
    $votes = $query->fetchAll();
    $fullvotesleft = 3;

    foreach($votes as $vote) {
        $fullvotesleft -= float_eq($vote['participant'], 1) ? 1 : 0;
        foreach($bofs as &$bof) {
            if($vote['workshop_id'] === $bof['id'] && float_eq($vote['participant'], 1.0))
                $bof['fullvote'] = True;
            if($vote['workshop_id'] === $bof['id'] && float_eq($vote['participant'], 0.25))
                $bof['quartervote'] = True;
        }
    }
    $stage =new ICCM\BOF\Stage($this->db);
    $stage2 =$stage->getstage();
    $params = $request->getQueryParams();
    $show_vote_message = array_key_exists('voted', $params) && $params['voted'] === '1';
    return $this->view->render($response, 'topics.html', [
        'bofs' => $bofs,
        'stage' => $stage2,
        'locked' =>  $stage2=='locked',
        'newuser' => $params['newuser'],
        'loggedin' => True,
        'left_votes' => $fullvotesleft,
        'voted_successfull' => $show_vote_message,
    ]);
})->setName('topics');

?>
