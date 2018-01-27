<?php

require __DIR__.'/../vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__.'/../settings.php';
$app = new \Slim\App($settings);

$container = $app->getContainer();

// Register dependencies
require __DIR__ . '/../dependencies.php';

// Register middleware
require __DIR__ . '/../middleware.php';

// Register routes
require __DIR__ . '/../routes/home.php';
require __DIR__ . '/../routes/auth.php';
require __DIR__ . '/../routes/admin.php';
require __DIR__ . '/../routes/nomination.php';
require __DIR__ . '/../routes/voting.php';

$app->run();
