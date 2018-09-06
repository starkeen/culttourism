<?php

$fileName = sprintf('%s/feed/%s', _DIR_DATA, 'turbo-blog.xml');

$xml = new SimpleXMLElement('<rss></rss>');
$xml->addAttribute('xmlns:yandex', 'http://news.yandex.ru');
$xml->addAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
$xml->addAttribute('xmlns:turbo', 'http://turbo.yandex.ru');
$xml->addAttribute('version', '2.0');

$xmlChannel = $xml->addChild('channel');
$xmlChannel->addChild('title', 'Культурный туризм: блог');
$xmlChannel->addChild('link', _SITE_URL);
$xmlChannel->addChild('description', 'Блог проекта Культурный туризм');
$xmlChannel->addChild('language', 'ru');

$blogModel = new MBlogEntries($db);
$blogEntries = $blogModel->getLastActive(10);
foreach ($blogEntries as $entry) {
    $xmlItem = $xmlChannel->addChild('item');
    $xmlItem->addAttribute('turbo', 'true');
    $xmlItem->addChild('link', $entry['br_link']);
    $xmlItem->addChild('title', $entry['br_title']);

    $content = $entry['br_text_absolute'];
    $itemTurboContent = $xmlItem->addChild('turbo:content', sprintf('<![CDATA[%s', $content), 'http://turbo.yandex.ru');
}

file_put_contents($fileName, $xml->asXML());
