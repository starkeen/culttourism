<?php

use app\model\criteria\PointCriteria;
use app\rss\YandexTurboPointsGenerator;

$fileName = sprintf('%s/feed/%s', _DIR_DATA, 'turbo-point.xml');

$pointModel = new MPagePoints($db);

$generator = new YandexTurboPointsGenerator($pointModel);

$criteria = new PointCriteria();
$criteria->setLimit(1000);

$xml = $generator->getXML($criteria);

file_put_contents($fileName, $xml->asXML());
