<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__.'/../classes/Auth.php';

$app->get('/login', function (Request $request, Response $response, array $args) {
    $allgetvars = $request->getQueryParams();
    $message = '';
    if (($allgetvars['message'] == 'invalid') || ($allgetvars['message'] == 'waitformoderation')) {
        $message = $allgetvars['message'];
    } else if ($allgetvars['newuser'] == 1) {
        $settings = require __DIR__.'/../../cfg/settings.php';
        if ($settings['settings']['moderated_registration']) {
            $message = 'newuser_waitformoderation';
        }
    } else if ($allgetvars['confirmuser'] == 1) {
        $message = 'confirmuser';
    }
    if ($message != '') {
        return $this->view->render($response, 'login.html', ['message' => $message]);
    }
    return $this->view->render($response, 'login.html');
})->setName('login');

$app->get('/register', function (Request $request, Response $response, array $args) {
    $settings = require __DIR__.'/../../cfg/settings.php';
    return $this->view->render($response, 'register.html', ['moderated_registration' => $settings['settings']['moderated_registration']]);
})->setName('register');

$app->post('/authenticate', 'ICCM\BOF\Auth:authenticate')->setName('authenticate');

$app->post('/new_user', 'ICCM\BOF\Auth:new_user')->setName('new_user');

$app->post('/confirm_user', 'ICCM\BOF\Auth:confirm_user')->setName('confirm_user');
$app->get('/confirm_user', 'ICCM\BOF\Auth:confirm_user')->setName('confirm_user_get');

$app->get('/logout', 'ICCM\BOF\Auth:logout')->setName('logout');

?>
