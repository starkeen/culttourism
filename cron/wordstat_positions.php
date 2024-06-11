<?php

use app\crontab\WordstatPositionsCommand;
use GuzzleHttp\Client;
use YandexSearchAPI\YandexSearchService;

$currentTime = date('H:i');

// Проверяем, попадает ли текущее время в диапазон от 00:00 до 08:00
if ($currentTime >= '00:00' && $currentTime < '08:00') {
    // Завершаем выполнение скрипта, экономим бюджет Яндекс Search API
    exit();
}

$ws = new MWordstat($db);
$searchService = new YandexSearchService(new Client(), $logger);
$searchService->setApiId(YANDEX_SEARCH_ID);
$searchService->setApiKey(YANDEX_SEARCH_KEY);

$command = new WordstatPositionsCommand($ws, $searchService, $logger);
$command->run();
