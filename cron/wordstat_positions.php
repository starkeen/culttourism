<?php

use app\crontab\WordstatPositionsCommand;
use GuzzleHttp\Client;
use YandexSearchAPI\YandexSearchService;

$ws = new MWordstat($db);
$searchService = new YandexSearchService(new Client(), $logger);
$searchService->setApiId(YANDEX_SEARCH_ID);
$searchService->setApiKey(YANDEX_SEARCH_KEY);

$command = new WordstatPositionsCommand($ws, $searchService, $logger);
$command->run();
