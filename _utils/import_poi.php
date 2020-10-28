<?php

declare(strict_types=1);

use app\db\MyDB;
use app\sys\Logger;
use app\sys\SentryLogger;

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

include dirname(__DIR__) . '/vendor/autoload.php';
include(dirname(__DIR__) . '/config/configuration.php');

$sentryLogger = new SentryLogger(SENTRY_DSN);
$logger = new Logger($sentryLogger);

$db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);

$json = file_get_contents('poi.json');

$data = json_decode($json);

$items = [];
foreach ($data[1][0][13][0] as $item) {
    $items[] = [
        'key' => $item[0],
        'name' => $item[5][0][1][0],
        'description' => $item[5][1][1][0] ?? null,
        'pictures' => $item[5][2][0][1] ?? null,
        'lat' => $item[1][0][0][0],
        'lon' => $item[1][0][0][1],
    ];
}

print_x($items);

if (isset($_GET['run'])) {
    $model = new MCandidatePoints($db);
    foreach ($items as $point) {
        $model->add([
            'cp_title' => $point['name'],
            'cp_text' => $point['description'],
            'cp_type_id' => 0,
            'cp_latitude' => $point['lat'],
            'cp_longitude' => $point['lon'],
        ]);
    }
}
