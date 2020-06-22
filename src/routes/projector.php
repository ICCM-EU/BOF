<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__.'/../classes/Projector.php';

$app->get('/projector', function($request, $response, $args) {
    global $app;
    $stage = $app->getContainer()->get('ICCM\BOF\Stage');
    $projector = $app->getContainer()->get('ICCM\BOF\Projector');
    $projector->showProjectorView ($request, $response, $stage, $args);
})->setName('projector');

?>
