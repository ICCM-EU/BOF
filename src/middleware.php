<?php
use \Firebase\JWT\JWT;

// the authentication
$app->add(new \Tuupola\Middleware\JwtAuthentication([
    "secure" => false, // we know we are using https behind a proxy
    "cookie" => "authtoken",
    "path" => [ "/admin", "/vote", "/nomination", "/topics", "/moderation", "/projector"],
    #"passthrough" => ["/home", "/login", "/authenticate"],
    "secret" => $settings['settings']['secrettoken'],
    "error" => function ($response, $arguments) {
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

    $sql_username = 'SELECT name FROM participant WHERE id = :uid';
    $sth = $this->db->prepare($sql_username);
    $sth->execute(['uid' => $cookie['userid']]);
    $username = NULL;
    $results = $sth->fetchAll();
    if(count($results) > 0)
        $username = $results[0];

    $container['view']['userid'] = $cookie['userid'];
    $container['view']['username'] = $username['name'];
    $container['view']['is_admin'] = $cookie['is_admin'];
    $request = $request->withAttribute('userid', $cookie['userid']);
    $request = $request->withAttribute('is_admin', $cookie['is_admin']);

    return $next($request, $response);
});

?>
