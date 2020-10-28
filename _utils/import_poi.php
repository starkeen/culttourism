<?php

declare(strict_types=1);

use app\db\MyDB;

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
include(dirname(__DIR__) . '/config/configuration.php');
include(_DIR_ROOT . '/includes/debug.php');

include(_DIR_INCLUDES . '/class.Helper.php');
spl_autoload_register('Helper::autoloader');

$db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);

$json = file_get_contents('poi.json');

$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
