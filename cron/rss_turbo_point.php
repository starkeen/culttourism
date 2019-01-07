<?php

use app\api\YandexWebmasterAPI;
use app\model\criteria\PointCriteria;
use app\rss\YandexTurboPointsGenerator;
use GuzzleHttp\Client;

$pointModel = new MPagePoints($db);
$generator = new YandexTurboPointsGenerator($pointModel);

$criteria = new PointCriteria();
$criteria->addWhere('LENGTH(pt_description) > 10');
$criteria->setLimit(1000);
$criteria->addOrder('pt_rank', PointCriteria::ORDER_DESC);

$xml = $generator->getXML($criteria);
$fileName = sprintf('%s/feed/%s', _DIR_DATA, 'turbo-point.xml');
file_put_contents($fileName, $xml->asXML());

$chunkNumber = date('d') % 10;
$dailyCriteria = clone $criteria;
$dailyCriteria->addWhere('RIGHT(CAST(pt_id AS CHAR), 1) = ' . $chunkNumber;
$xml = $generator->getXML($dailyCriteria);
$fileName = sprintf('%s/feed/turbo-point-d%02d.xml', _DIR_DATA, $chunkNumber);
file_put_contents($fileName, $xml->asXML());

$guzzle = new Client();
$apiClient = new YandexWebmasterAPI($guzzle);
$apiClient->uploadRSS($fileName);
