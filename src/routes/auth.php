<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__.'/../classes/Auth.php';

$app->get('/login', function (Request $request, Response $response, array $args) {
    return $this->view->render($response, 'login.html');
})->setName('login');

$app->get('/register', function (Request $request, Response $response, array $args) {
    $settings = require __DIR__.'/../../cfg/settings.php';
    return $this->view->render($response, 'register.html', ['moderated_registration' => $settings['settings']['moderated_registration']]);
})->setName('register');

$app->post('/authenticate', 'ICCM\BOF\Auth:authenticate')->setName('authenticate');

$app->post('/new_user', 'ICCM\BOF\Auth:new_user')->setName('new_user');
$app->get('/reset_pwd', 'ICCM\BOF\Auth:reset_pwd')->setName('reset_pwd_get');
$app->post('/reset_pwd', 'ICCM\BOF\Auth:reset_pwd')->setName('reset_pwd');

$app->post('/confirm_user', 'ICCM\BOF\Auth:confirm_user')->setName('confirm_user');
$app->get('/confirm_user', 'ICCM\BOF\Auth:confirm_user')->setName('confirm_user_get');
$app->get('/settings', 'ICCM\BOF\Auth:edit_settings')->setName('settings');
$app->post('/settings', 'ICCM\BOF\Auth:edit_settings')->setName('settings_save');

$app->get('/logout', 'ICCM\BOF\Auth:logout')->setName('logout');

?>
