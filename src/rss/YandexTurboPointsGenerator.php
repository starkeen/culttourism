<?php

declare(strict_types=1);

namespace app\rss;

use app\exceptions\MyPDOException;
use app\model\criteria\PointCriteria;
use MPagePoints;
use SimpleXMLElement;

class YandexTurboPointsGenerator
{
    /**
     * @var MPagePoints
     */
    private $pointModel;

    /**
     * @param MPagePoints $pointModel
     */
    public function __construct(MPagePoints $pointModel)
    {
        $this->pointModel = $pointModel;
    }

    /**
     * @param PointCriteria $criteria
     *
     * @return SimpleXMLElement
     * @throws MyPDOException
     */
    public function getXML(PointCriteria $criteria): SimpleXMLElement
    {
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

        $entries = $this->pointModel->getActiveSights($criteria);
        foreach ($entries as $entry) {
            $xmlItem = $xmlChannel->addChild('item');
            $xmlItem->addAttribute('turbo', 'true');

            $cityUrl = _SITE_URL . ltrim($entry['city_url'], '/');
            $xmlItem->addChild('link', $cityUrl . $entry['pt_slugline'] . '.html');
            $xmlItem->addChild('title', $entry['pt_name']);

            $content = htmlspecialchars($entry['text_absolute']);
            if ($entry['photo_src'] !== null && trim($entry['photo_src']) !== '') {
                $absolutePhotoUrl = $entry['photo_src'];
                if (strpos($absolutePhotoUrl, '/') === 0) {
                    $absolutePhotoUrl = _SITE_URL . ltrim($absolutePhotoUrl, '/');
                }
                $content = '<figure><img src="' . $absolutePhotoUrl . '"></figure>' . $content;
            }
            $contactsBlock = '';
            foreach (['pt_adress', 'pt_phone', 'pt_worktime', 'pt_website'] as $contactType) {
                if ($entry[$contactType] !== null && trim($entry[$contactType]) !== '') {
                    $contactValue = $entry[$contactType];
                    if ($contactType === 'pt_website') {
                        $contactValue = sprintf('<a href="%s">%s</a>', $contactValue, $contactValue);
                    }
                    $contactsBlock .= '<li>' . $contactValue . '</li>';
                }
            }
            if ($contactsBlock !== '') {
                $content .= '<p>контактная информация:';
                $content .= '<ul>' . $contactsBlock . '</ul>';
                $content .= '</p>';
            }
            $content .= sprintf('<p><a href="%s">%s</a></p>', $cityUrl, 'Достопримечательности ' . $entry['pc_inwheretext']);

            $xmlItem->addChild('turbo:content', sprintf('<![CDATA[%s', $content), 'http://turbo.yandex.ru');
        }

        return $xml;
    }
}
