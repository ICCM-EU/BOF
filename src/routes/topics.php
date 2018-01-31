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
$app->get('/topics', function (Request $request, Response $response, array $args) {
    $sql = 'SELECT * FROM `workshop`';
    $query=$this->db->prepare($sql);
    $param = array ();
    $query->execute($param);
    $bofs = array ();
    while ($row=$query->fetch(PDO::FETCH_OBJ)) {
        $bofs [] = $row;
    }

    $sql = 'SELECT workshop_id FROM `workshop_participant` WHERE participant = 1 AND participant_id = 83';
    $query = $this->db->prepare($sql);
    $param = array ();
    $query->execute($param);
    
    $voted_for = array ();
    while ($row=$query->fetch(PDO::FETCH_OBJ)) {
        $voted_for [] = $row;
    }

    return $this->view->render($response, 'topics.html', [
        'bofs' => $bofs,
        'stage' => 'voting',
        'locked' => False,
        'newuser' => True,
        'loggedin' => True,
        'voted_count' => $query->rowCount(),
    ]);
})->setName('topics');

?>
