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
    $result = $sth->fetchAll();

    return $this->view->render($response, 'votelist.html', [
        //'debug' => print_r($result, TRUE),
        'votes' => $result,
    ]);

});

$app->get('/votes/add', function (Request $request, Response $response, array $args) {
    $sql_get_votes = 'SELECT * FROM workshop';
    $sth = $this->db->prepare($sql_get_votes);
    $sth->execute();
    $workshop = $sth->fetchAll();

    return $this->view->render($response, 'addvote.html', [
        'workshops' => $workshop
    ]);
});

$app->post('/votes/add', function (Request $request, Response $response, array $args) {
    $data = $request->getParsedBody();
    $vote = [];
    $response->getBody()->write('You voted: ' . $data['vote'] . ' for workshopid ' . $data['workshopid']);
    return $response;
});

?>
