<?php

include __DIR__ . '/vendor/autoload.php';

use app\core\application\WebApplication;

include 'config/configuration.php';
$app = new WebApplication();
$app->run();
