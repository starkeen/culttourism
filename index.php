<?php

require_once __DIR__ . '/vendor/autoload.php';

use app\core\application\WebApplication;

require_once 'config/configuration.php';
$app = new WebApplication();
$app->run();
