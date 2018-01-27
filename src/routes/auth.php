<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

$app->get('/login', function (Request $request, Response $response, array $args) {
    return $this->view->render($response, 'login.html');
})->setName('login');

$app->post('/authenticate', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $login = $data['user_name'];
    $password = $data['password'];
    if ($login == "admin" && $password == "admin") {
        $payload = array("is_admin" => true);
        $token = JWT::encode($payload, $this->settings['secrettoken'], "HS256");
        setcookie("authtoken", $token, time()+3600);  // cookie expires in one hour
        global $app;
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("admin"))->withStatus(302);
    } else {
        echo json_encode("No valid user or password");
    }
})->setName('authenticate');

$app->get('/logout', function (Request $request, Response $response, array $args) {
    setcookie("authtoken", "", time()-3600);
    return $this->view->render($response, 'loggedout.html');
})->setName('logout');

?>
