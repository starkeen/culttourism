<?php

declare(strict_types=1);

namespace app\crontab;

use MPageCities;
use SimpleXMLElement;

class RssTurboCityCommand extends AbstractCrontabCommand
{
    /**
     * @var MPageCities
     */
    private $itemsModel;

    public function __construct(MPageCities $itemsModel)
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
        $xmlChannel->addChild('title', 'Культурный туризм: города');
        $xmlChannel->addChild('link', GLOBAL_SITE_URL);
        $xmlChannel->addChild('description', 'Культурный туризм');
        $xmlChannel->addChild('language', 'ru');

        $entries = $this->itemsModel->getActive();
        foreach ($entries as $entry) {
            $xmlItem = $xmlChannel->addChild('item');
            $xmlItem->addAttribute('turbo', 'true');
            $xmlItem->addChild('link', GLOBAL_SITE_URL . ltrim($entry['city_url'], '/'));
            $xmlItem->addChild('title', 'Достопримечательности ' . $entry['pc_inwheretext']);

            $content = $entry['text_absolute'];
            if ($entry['photo_src'] !== '') {
                $absolutePhotoUrl = $entry['photo_src'];
                if (strpos($absolutePhotoUrl, '/') === 0) {
                    $absolutePhotoUrl = GLOBAL_SITE_URL . ltrim($absolutePhotoUrl, '/');
                }
                $content = '<figure><img src="' . $absolutePhotoUrl . '"></figure>' . $content;
            }
            $xmlItem->addChild(
                'turbo:content',
                sprintf('<![CDATA[%s]]>', $content),
                'http://turbo.yandex.ru'
            );
        }

        file_put_contents($fileName, $xml->asXML());
    }
}
