<?php

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['name'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['view'] = function ($container) {
    $sql = "SELECT item, value from `config` WHERE item = 'branding'";
    $query=$container['db']->prepare($sql);
    $query->execute();
    $cfg = array();
    while ($row=$query->fetch(PDO::FETCH_OBJ)) {
        $cfg[$row->item] = $row->value;
    }

    $view = new \Slim\Views\Twig('../templates/', [
        # we could use the caching by setting a path here, eg. ../cache
        'cache' => false
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
    $view->getEnvironment()->addGlobal('branding', $cfg['branding']);

    return $view;
};

$container['ICCM\BOF\Auth'] = function ($c) {
    global $app;
    global $settings;
    global $translator;
    return new \ICCM\BOF\Auth(
        $c['view'],
        $app->getContainer()->get('router'),
        $app->getContainer()->get('ICCM\BOF\DBO'),
        $settings['settings']['secrettoken'],
        $app->getContainer()->get('ICCM\BOF\Cookies'),
        $translator
    );
};

$container['ICCM\BOF\Admin'] = function ($c) {
    global $app;
    global $settings;
    return new \ICCM\BOF\Admin(
        $c['view'],
        $c['db'],
        $app->getContainer()->get('router'));
};

$container['ICCM\BOF\Cookies'] = function ($c) {
    return new \ICCM\BOF\Cookies();
};

$container['ICCM\BOF\DBO'] = function ($c) {
    global $app;
    return new \ICCM\BOF\DBO($c['db']);
};

$container['ICCM\BOF\Nomination'] = function ($c) {
    global $app;
    global $settings;
    return new \ICCM\BOF\Nomination(
        $c['view'],
        $app->getContainer()->get('router'),
        $app->getContainer()->get('ICCM\BOF\DBO'));
};

$container['ICCM\BOF\Moderation'] = function ($c) {
    global $app;
    global $settings;
    return new \ICCM\BOF\Moderation(
        $c['view'],
        $app->getContainer()->get('router'),
        $app->getContainer()->get('ICCM\BOF\DBO'));
};

$container['ICCM\BOF\Projector'] = function ($c) {
    global $app;
    global $settings;
    return new \ICCM\BOF\Projector(
        $c['view'],
        $app->getContainer()->get('router'),
        $app->getContainer()->get('ICCM\BOF\DBO'));
};

?>
