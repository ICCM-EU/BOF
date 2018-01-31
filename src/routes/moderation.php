<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__.'/../classes/Moderation.php';

$app->get('/moderation', 'ICCM\BOF\Moderation:showModerationView')->setName('moderation	');

$app->post('/moderation', 'ICCM\BOF\Moderation:moderate')->setName('moderation');

?>
