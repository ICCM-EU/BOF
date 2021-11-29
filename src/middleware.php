<?php
use \Firebase\JWT\JWT;

// the authentication
$app->add(new \Slim\Middleware\JwtAuthentication([
    "secure" => false, // we know we are using https behind a proxy
    "cookie" => "authtoken",
    "path" => [ "/admin", "/vote", "/nomination", "/topics", "/moderation", "/projector"],
    #"passthrough" => ["/home", "/login", "/authenticate"],
    "secret" => $settings['settings']['secrettoken'],
    "error" => function ($request, $response, $arguments) {
        return $response->withRedirect("/?message=invalid_login")->withStatus(302);
    }
]));


$app->add(function($request, $response, $next) {
    global $settings;
    global $container;

    if(!array_key_exists('authtoken', $request->getCookieParams()))
        return $next($request, $response);
    $encodedcookie = $request->getCookieParams()['authtoken'];

    $cookie = (array)JWT::decode($encodedcookie, $settings['settings']['secrettoken'], array('HS256'));

    $sql_username = 'SELECT name, is_admin, is_moderator FROM participant WHERE id = :uid';
    $sth = $this->db->prepare($sql_username);
    $sth->execute(['uid' => $cookie['userid']]);
    $username = NULL;
    $results = $sth->fetchAll();
    if(count($results) > 0) {
        $username = $results[0]['name'];
        $is_admin = $results[0]['is_admin'];
        $is_moderator = $results[0]['is_moderator'];
    }

    $container['view']['userid'] = $cookie['userid'];
    $container['view']['username'] = $username;
    $container['view']['is_admin'] = $is_admin;
    $container['view']['is_moderator'] = $is_moderator;
    $request = $request->withAttribute('userid', $cookie['userid']);
    $request = $request->withAttribute('is_admin', $is_admin);
    $request = $request->withAttribute('is_moderator', $is_moderator);

    return $next($request, $response);
});

?>
