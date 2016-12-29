<?php

/**
 * @property string $title
 * @property string $link
 * @property string $description
 * @property string $managingEditor
 * @property string $webMaster
 */
class RSSGenerator
{
    protected $xml;

    protected $props = [
        'title' => '',
        'link' => '',
        'description' => '',
        'managingEditor' => 'common@ourways.ru (OURWAYS.RU editor)',
        'webMaster' => 'starkeen@ourways.ru (Andrey Panisko)',
    ];

    public function __construct()
    {
        $this->xml = $this->buildXML();
        $this->xml->addAttribute('version', '2.0');
    }

    /**
     * @param array $data
     * @return string
     */
    public function process(array $data)
    {
        $channel = $this->xml->addChild('channel');
        $channel->addChild('title', $this->props['title']);
        $channel->addChild('link', $this->props['link']);
        $channel->addChild('description', $this->props['description']);
        $channel->addChild('managingEditor', $this->props['managingEditor']);
        $channel->addChild('webMaster', $this->props['webMaster']);
        $channel->addChild('lastBuildDate',date('r'));
        $channel->addChild('pubDate',date('r'));
        $channel->addChild('generator', 'RSS-gen / '. $this->props['link']);
        $channel->addChild('language', 'ru-RU');

        foreach ($data as $entry) {
            $item = $this->xml->addChild('item');
            $entity = $this->mapEntity($entry);
            $item->addChildWithCData('title', $entity['title']);
            $item->addChild('guid', $entity['link'])->addAttribute('isPermaLink', 'true');
            $item->addChild('link', $entity['link']);
            $item->addChild('pubDate', $entity['date']);
            $item->addChildWithCData('description',$entity['text']);
            $item->addChildWithCData('content:encoded', $entity['text'], 'content');
            $item->addChild('author', $entity['author']);
            $item->addChild('dc:creator', $entity['author'], 'dc');
        }

        return $this->xml->asXML();
    }

    /**
     * @param array $entry
     * @return array
     */
    protected function mapEntity(array $entry)
    {
        return [
            'title' => $entry['br_title'],
            'link' => $entry['br_link'],
            'date' => $entry['bg_pubdate'],
            'text' => $entry['br_text_absolute'],
            'author' => $entry['us_name'],
        ];
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value)
    {
        $this->props[$name] = $value;
    }

    /**
     * @return SimpleXMLElement
     */
    private function buildXML()
    {
        $docType = '<?xml version="1.0" encoding="UTF-8"?><rss xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom"/>';
        return new RSSElement($docType);
    }
}