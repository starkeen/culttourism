<?php

use app\api\YandexWebmasterAPI;
use app\model\criteria\PointCriteria;
use app\rss\YandexTurboPointsGenerator;
use GuzzleHttp\Client;

$pointModel = new MPagePoints($db);
$generator = new YandexTurboPointsGenerator($pointModel);

$baseCriteria = new PointCriteria();
$baseCriteria->addWhere('LENGTH(pt_description) > 10');
$baseCriteria->addOrder('pt_rank', PointCriteria::ORDER_DESC);

$criteria = clone $baseCriteria;
$criteria->setLimit(1000);
$xml = $generator->getXML($criteria);
$fileName = sprintf('%s/feed/%s', _DIR_DATA, 'turbo-point.xml');
file_put_contents($fileName, $xml->asXML());

$chunkNumber = date('d') % 10;
$dailyCriteria = clone $baseCriteria;
$dailyCriteria->setLimit(10g000);
$dailyCriteria->addWhere('RIGHT(CAST(pt_id AS CHAR), 1) = ' . $chunkNumber);
$xml = $generator->getXML($dailyCriteria);
$fileName = sprintf('%s/feed/turbo-point-d%02d.xml', _DIR_DATA, $chunkNumber);
file_put_contents($fileName, $xml->asXML());

$guzzle = new Client();
$apiClient = new YandexWebmasterAPI($guzzle);
$apiClient->uploadRSS($fileName);
