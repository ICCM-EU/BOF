<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__.'/../classes/Projector.php';

$app->get('/projector', 'ICCM\BOF\Projector:showProjectorView')->setName('projector');

?>
