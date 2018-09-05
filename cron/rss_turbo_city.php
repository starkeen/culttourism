<?php

$fileName = sprintf('%s/feed/%s', _DIR_DATA, 'turbo-city.xml');

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss></rss>');
$xml->addAttribute('xmlns:yandex', 'http://news.yandex.ru');
$xml->addAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
$xml->addAttribute('xmlns:turbo', 'http://turbo.yandex.ru');
$xml->addAttribute('version', '2.0');

$xmlChannel = $xml->addChild('channel');
$xmlChannel->addChild('title', 'Культурный туризм: города');
$xmlChannel->addChild('link', _SITE_URL);
$xmlChannel->addChild('description', 'Культурный туризм');
$xmlChannel->addChild('language', 'ru');

$blogModel = new MPageCities($db);
$entries = $blogModel->getActive();
foreach ($entries as $entry) {
    $xmlItem = $xmlChannel->addChild('item');
    $xmlItem->addAttribute('turbo', 'true');
    $xmlItem->addChild('link', _SITE_URL . ltrim('/', $entry['city_url']));
    $xmlItem->addChild('title', 'Достопримечательности '. $entry['pc_inwheretext']);

    $content = $entry['text_absolute'];
    $itemTurboContent = $xmlItem->addChild('turbo:content', sprintf('<![CDATA[%s]]>', $content), 'http://turbo.yandex.ru');
}

file_put_contents($fileName, $xml->asXML());
