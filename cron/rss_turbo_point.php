<?php

$fileName = sprintf('%s/feed/%s', _DIR_DATA, 'turbo-point.xml');

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss></rss>');
$xml->addAttribute('xmlns:yandex', 'http://news.yandex.ru');
$xml->addAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
$xml->addAttribute('xmlns:turbo', 'http://turbo.yandex.ru');
$xml->addAttribute('version', '2.0');

$xmlChannel = $xml->addChild('channel');
$xmlChannel->addChild('title', 'Культурный туризм: точки');
$xmlChannel->addChild('link', _SITE_URL);
$xmlChannel->addChild('description', 'Культурный туризм');
$xmlChannel->addChild('language', 'ru');

$pointModel = new MPagePoints($db);
$entries = $pointModel->getActiveSights(1000);
foreach ($entries as $entry) {
    $xmlItem = $xmlChannel->addChild('item');
    $xmlItem->addAttribute('turbo', 'true');
    $xmlItem->addChild('link', _SITE_URL . ltrim($entry['city_url'], '/') . $entry['pt_slugline'] . '.html');
    $xmlItem->addChild('title', $entry['pt_name']);

    $content = htmlspecialchars($entry['text_absolute']);
    if (trim($entry['photo_src']) !== '') {
        $absolutePhotoUrl = $entry['photo_src'];
        if (strpos($absolutePhotoUrl, '/') === 0) {
            $absolutePhotoUrl = _SITE_URL . ltrim($absolutePhotoUrl, '/');
        }
        $content = '<figure><img src="' . $absolutePhotoUrl . '"></figure>' . $content;
    }
    $content .= '<p>контактная информация</p>';
    $itemTurboContent = $xmlItem->addChild('turbo:content', sprintf('<![CDATA[%s', $content), 'http://turbo.yandex.ru');
}

file_put_contents($fileName, $xml->asXML());
