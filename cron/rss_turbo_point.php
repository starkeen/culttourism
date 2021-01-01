<?php

use app\api\YandexWebmasterAPI;
use app\crontab\RssTurboPointsCommand;
use app\rss\YandexTurboPointsGenerator;
use GuzzleHttp\Client;

$pointModel = new MPagePoints($db);
$generator = new YandexTurboPointsGenerator($pointModel);

$guzzle = new Client();
$apiClient = new YandexWebmasterAPI($guzzle);

$command = new RssTurboPointsCommand($generator, $apiClient);

$fileName = sprintf('%s/feed/%s', GLOBAL_DIR_DATA, 'turbo-point.xml');
$chunkNumber = date('d') % 10;
$partialFileName = sprintf('%s/feed/turbo-point-d%02d.xml', GLOBAL_DIR_DATA, $chunkNumber);

$command->run($fileName, $partialFileName, $chunkNumber);
