<?php

require __DIR__.'/../vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__.'/../../cfg/settings.php';
$app = new \Slim\App($settings);

$container = $app->getContainer();

// Register dependencies
require __DIR__ . '/../dependencies.php';
require __DIR__.'/../classes/Results.php';
require __DIR__.'/../classes/Cookies.php';
require __DIR__.'/../classes/DBO.php';
require __DIR__.'/../classes/Logger.php';
require __DIR__.'/../classes/Timezones.php';

// Register middleware
require __DIR__ . '/../middleware.php';

// Register translation
require __DIR__ . '/../i18n.php';

// Register routes
require __DIR__ . '/../routes/home.php';
require __DIR__ . '/../routes/auth.php';
require __DIR__ . '/../routes/admin.php';
require __DIR__ . '/../routes/nomination.php';
require __DIR__ . '/../routes/voting.php';
require __DIR__ . '/../routes/projector.php';
require __DIR__ . '/../routes/moderation.php';
require __DIR__ . '/../routes/topics.php';

// Set the default timezone used by all date/time functions
date_default_timezone_set('UTC');

$app->run();
