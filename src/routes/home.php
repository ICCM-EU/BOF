<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/', function (Request $request, Response $response, array $args) {
    if($request->getAttribute('userid') !== NULL)
        return $response->withRedirect($this->router->pathFor("topics"))->withStatus(302);
    $config['show_githubforkme'] = true;
    $settings = require __DIR__.'/../../cfg/settings.php';
    $config['website_type'] = $settings['settings']['website_type'];
    $config['workshop_icon'] = $settings['settings']['workshop_icon'];
    return $this->view->render($response, 'home.html', $config);
})->setName('home');

?>
