<?php

use app\model\criteria\PointCriteria;
use app\rss\YandexTurboPointsGenerator;

$pointModel = new MPagePoints($db);
$generator = new YandexTurboPointsGenerator($pointModel);

$criteria = new PointCriteria();
$criteria->addWhere('LENGTH(pt_description) > 10');
$criteria->setLimit(1000);
$criteria->addOrder('pt_rank', PointCriteria::ORDER_DESC);

$xml = $generator->getXML($criteria);
$fileName = sprintf('%s/feed/%s', _DIR_DATA, 'turbo-point.xml');
file_put_contents($fileName, $xml->asXML());

$dayToday = date('d');
$dailyCriteria = clone $criteria;
$dailyCriteria->addWhere('RIGHT(CAST(pt_id AS CHAR), 2) = ' . sprintf('%02d', $dayToday - 1));
$xml = $generator->getXML($dailyCriteria);
$fileName = sprintf('%s/feed/turbo-point-d%02d.xml', _DIR_DATA, $dayToday);
file_put_contents($fileName, $xml->asXML());
