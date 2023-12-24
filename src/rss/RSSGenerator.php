<?php

namespace app\rss;

use SimpleXMLElement;

/**
 * @property string $title
 * @property string $link
 * @property string $url
 * @property string $description
 * @property string $managingEditor
 * @property string $webMaster
 * @property string $email
 */
class RSSGenerator implements IRSSGenerator
{
    protected $props = [
        'title' => '',
        'link' => '',
        'url' => '',
        'description' => '',
        'email' => '',
        'managingEditor' => 'common@ourways.ru (OURWAYS.RU editor)',
        'webMaster' => 'starkeen@ourways.ru (Andrey Panisko)',
    ];

    /**
     * @param  array $data
     * @return string
     */
    public function process(array $data)
    {
        $xml = $this->buildXML();
        $xml->addAttribute('version', '2.0');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', $this->props['title']);
        $channel->addChild('link', $this->props['link']);
        $channel->addChild('description', $this->props['description']);
        $channel->addChild('managingEditor', $this->props['managingEditor']);
        $channel->addChild('webMaster', $this->props['webMaster']);
        $channel->addChild('lastBuildDate', date('r'));
        $channel->addChild('pubDate', date('r'));
        $channel->addChild('generator', 'RSS-gen / '. $this->props['link']);
        $channel->addChild('language', 'ru-RU');
        $atom = $channel->addChild('atom:link', null, 'http://www.w3.org/2005/Atom');
        $atom ->addAttribute('href', $this->props['url']);
        $atom->addAttribute('rel', 'self');
        $atom->addAttribute('type', 'application/rss+xml');

        foreach ($data as $entry) {
            $item = $channel->addChild('item');
            $entity = $this->mapEntity($entry);
            $item->addChildWithCData('title', $entity['title']);
            $item->addChild('guid', $entity['link'])->addAttribute('isPermaLink', 'true');
            $item->addChild('link', $entity['link']);
            $item->addChild('pubDate', $entity['date']);
            $item->addChildWithCData('description', $entity['text']);
            $item->addChild('dc:creator', $entity['creator'], 'http://purl.org/dc/elements/1.1/');
        }

        return $xml->asXML();
    }

    /**
     * @param  array $entry
     * @return array
     */
    protected function mapEntity(array $entry): array
    {
        return [
            'title' => trim($entry['br_title']),
            'link' => $entry['br_link'],
            'date' => $entry['bg_pubdate'],
            'text' => $entry['br_text_absolute'],
            'creator' => $entry['us_name'],
            'author' => sprintf('%s (%s)', $entry['us_email'], $entry['us_name']),
        ];
    }

    /**
     * @return SimpleXMLElement
     */
    private function buildXML(): SimpleXMLElement
    {
        $docType = '<?xml version="1.0" encoding="UTF-8"?><rss xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom" />';
        return new RSSElement($docType);
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
     * @param string $name
     *
     * @return null
     */
    public function __get($name)
    {
        return $this->props[$name] ?? null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->props[$name]);
    }
}
