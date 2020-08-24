<?php

require __DIR__.'/../vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__.'/../../cfg/settings.php';

// Register dependencies
require __DIR__.'/../classes/Admin.php';
require __DIR__.'/../classes/Auth.php';
require __DIR__.'/../classes/Cookies.php';
require __DIR__.'/../classes/DBO.php';
require __DIR__.'/../classes/Logger.php';
require __DIR__.'/../classes/Moderation.php';
require __DIR__.'/../classes/Nomination.php';
require __DIR__.'/../classes/Projector.php';
require __DIR__.'/../classes/Results.php';

