<?php

include __DIR__ . '/vendor/autoload.php';

use app\core\WebApplication;

include 'config/configuration.php';
$app = new WebApplication();
$app->run();
