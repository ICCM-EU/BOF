<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/admin', function (Request $request, Response $response, array $args) {
    return $this->view->render($response, 'admin.html', [
        'loggedin' => True
    ]);
})->setName('admin');

?>
