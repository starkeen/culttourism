<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use SimpleXMLElement;

class QueryDoc
{
    /**
     * @var SimpleXMLElement
     */
    private $xml;

    public function __construct()
    {
        $this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><request></request>');
        $this->xml->addChild('query', '');
        $this->xml->addChild('page', '0');
        $sortBy = $this->xml->addChild('sortby', 'rlv');
        $sortBy->addAttribute('order', 'descending');
        $sortBy->addAttribute('priority', 'no');
        $this->xml->addChild('maxpassages', '100');
        $xmlGroup = $this->xml->addChild('groupings');
        $xmlGroupBy = $xmlGroup->addChild('groupby');
        $xmlGroupBy->addAttribute('attr', '');
        $xmlGroupBy->addAttribute('mode', 'flat');
        $xmlGroupBy->addAttribute('groups-on-page', '0');
        $xmlGroupBy->addAttribute('docs-in-group', '1');
        $xmlGroupBy->addAttribute('curcateg', '-1');
    }

    public function setKeywords(string $keywords): self
    {
        $this->xml->query = $keywords;

        return $this;
    }

    public function setPage(int $page): self
    {
        $this->xml->page = (string) $page;

        return $this;
    }

    public function setMaxPagesCount(int $count): self
    {
        $this->xml->maxpassages = (string) $count;

        return $this;
    }

    public function getString(): string
    {
        return $this->xml->asXML();
    }

    public function getLength(): int
    {
        return strlen($this->xml->asXML());
    }
}
