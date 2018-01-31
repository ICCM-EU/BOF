<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__.'/../classes/Nomination.php';

$app->get('/nomination', function (Request $request, Response $response, array $args) {
    return $this->view->render($response, 'nomination.html');
})->setName('nomination');

$app->post('/nomination', 'ICCM\BOF\Nomination:nominate')->setName('nomination');

?>
