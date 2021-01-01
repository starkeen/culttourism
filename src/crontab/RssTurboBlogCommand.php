<?php

declare(strict_types=1);

namespace app\crontab;

use MBlogEntries;
use SimpleXMLElement;

class RssTurboBlogCommand extends CrontabCommand
{
    /**
     * @var MBlogEntries
     */
    private $itemsModel;

    public function __construct(MBlogEntries $itemsModel)
    {
        $this->itemsModel = $itemsModel;
    }

    public function run(string $fileName): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss></rss>');
        $xml->addAttribute('xmlns:yandex', 'http://news.yandex.ru');
        $xml->addAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $xml->addAttribute('xmlns:turbo', 'http://turbo.yandex.ru');
        $xml->addAttribute('version', '2.0');

        $xmlChannel = $xml->addChild('channel');
        $xmlChannel->addChild('title', 'Культурный туризм: блог');
        $xmlChannel->addChild('link', GLOBAL_SITE_URL);
        $xmlChannel->addChild('description', 'Блог проекта Культурный туризм');
        $xmlChannel->addChild('language', 'ru');

        $blogEntries = $this->itemsModel->getLastActive(10);
        foreach ($blogEntries as $entry) {
            $xmlItem = $xmlChannel->addChild('item');
            $xmlItem->addAttribute('turbo', 'true');
            $xmlItem->addChild('link', $entry['br_link']);
            $xmlItem->addChild('title', $entry['br_title']);

            $content = $entry['br_text_absolute'];
            $xmlItem->addChild('turbo:content', sprintf('<![CDATA[%s]]>', $content), 'http://turbo.yandex.ru');
        }

        file_put_contents($fileName, $xml->asXML());
    }
}
