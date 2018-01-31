<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__.'/../classes/Auth.php';

$app->get('/login', function (Request $request, Response $response, array $args) {
    $allgetvars = $request->getQueryParams();
    if ($allgetvars['message'] == 'invalid') {
        return $this->view->render($response, 'login.html', ['message' => 'invalid']);
    }
    return $this->view->render($response, 'login.html');
})->setName('login');

$app->get('/register', function (Request $request, Response $response, array $args) {
    return $this->view->render($response, 'register.html');
})->setName('register');

$app->post('/authenticate', 'ICCM\BOF\Auth:authenticate')->setName('authenticate');

$app->post('/new_user', 'ICCM\BOF\Auth:new_user')->setName('new_user');

$app->get('/logout', 'ICCM\BOF\Auth:logout')->setName('logout');

?>
