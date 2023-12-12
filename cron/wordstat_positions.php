<?php

use app\crontab\WordstatPositionsCommand;
use app\services\YandexSearch\ServiceBuilder;

$ws = new MWordstat($db);
$searchService = ServiceBuilder::build();

$command = new WordstatPositionsCommand($ws, $searchService, $logger);
$command->run();
