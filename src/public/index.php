<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

require __DIR__.'/../vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__.'/../settings.php';
$app = new \Slim\App($settings);

$container = $app->getContainer();

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('../templates/', [
        # we could use the caching by setting a path here, eg. ../cache
        'cache' => false
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

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

$app->get('/', function (Request $request, Response $response, array $args) {
    return $this->view->render($response, 'home.html');
})->setName('home');

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    return $this->view->render($response, 'hello.html', [
        'name' => $args['name']
    ]);

})->setName('hello');

$app->get('/admin', function (Request $request, Response $response, array $args) {
    return $this->view->render($response, 'admin.html', [
        'loggedin' => true
    ]);
})->setName('admin');

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

$app->run();
