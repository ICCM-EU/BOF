<?php

function file_build_path(...$segments) {
    return join(DIRECTORY_SEPARATOR, $segments);
}

require file_build_path(__DIR__,"..", "vendor", "autoload.php");

// Instantiate the app
$settings = require file_build_path(__DIR__, "..", "..", "cfg", "settings.php");
$app = new \Slim\App($settings);

$container = $app->getContainer();

// Register dependencies
require file_build_path(__DIR__, "..", "dependencies.php");
require file_build_path(__DIR__, "..", "classes", "Results.php");
require file_build_path(__DIR__, "..", "classes", "Cookies.php");
require file_build_path(__DIR__, "..", "classes", "DBO.php");
require file_build_path(__DIR__, "..", "classes", "Logger.php");
require file_build_path(__DIR__, "..", "classes", "Timezones.php");

// Register middleware
require file_build_path(__DIR__, "..", "middleware.php");

// Register translation
require file_build_path(__DIR__, "..", "i18n.php");

// Register routes
require file_build_path(__DIR__, "..", "routes", "home.php");
require file_build_path(__DIR__, "..", "routes", "auth.php");
require file_build_path(__DIR__, "..", "routes", "admin.php");
require file_build_path(__DIR__, "..", "routes", "nomination.php");
require file_build_path(__DIR__, "..", "routes", "voting.php");
require file_build_path(__DIR__, "..", "routes", "projector.php");
require file_build_path(__DIR__, "..", "routes", "moderation.php");
require file_build_path(__DIR__, "..", "routes", "topics.php");

// Set the default timezone used by all date/time functions
date_default_timezone_set('UTC');

$app->run();
