<?php

// the authentication
$app->add(new \Slim\Middleware\JwtAuthentication([
    "secure" => false, // we know we are using https behind a proxy
    "cookie" => "authtoken",
    "path" => [ "/admin", "/vote", "/nominate"],
    #"passthrough" => ["/home", "/login", "/authenticate"],
    "secret" => $settings['settings']['secrettoken'],
    "error" => function ($request, $response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

?>
