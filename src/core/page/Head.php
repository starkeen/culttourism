<?php

declare(strict_types=1);

namespace app\core\page;

use app\constant\OgType;
use app\utils\Urls;

class Head
{
    /**
     * @var string[]
     */
    private $titleElements = [];

    /**
     * @var string[]
     */
    private $keywordsElements = [];

    /**
     * @var string[]
     */
    private $descriptionElements = [];

    /**
     * @var string
     */
    private $titleDelimiter = ' - ';

    /**
     * @var string|null
     */
    private $canonicalUrl;

    /**
     * @var array
     */
    private $microMarking = [];

    /**
     * @var array
     */
    private $customTags = [];

    /**
     * @var string
     */
    private $robotsIndexing = 'index,follow';

    public function addTitleElement(string $element): void
    {
        $this->titleElements[] = $element;
    }

    public function addKeyword(string $keyword): void
    {
        $this->keywordsElements[] = $keyword;
    }

    public function addDescription(string $keyword): void
    {
        $this->descriptionElements[] = $keyword;
    }

    public function getTitle(): string
    {
        $elements = $this->titleElements;
        krsort($elements);

        return implode($this->titleDelimiter, $elements);
    }

    public function getKeywords(): string
    {
        return implode(', ', $this->keywordsElements);
    }

    public function getDescription(): string
    {
        return implode('. ', $this->descriptionElements);
    }

    public function setTitleDelimiter(string $delimiter): void
    {
        $this->titleDelimiter = $delimiter;
    }

    /**
     * @return string|null
     */
    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl !== null ? Urls::getAbsoluteURL($this->canonicalUrl) : null;
    }

    /**
     * @param string $canonicalUrl
     */
    public function setCanonicalUrl(string $canonicalUrl): void
    {
        $this->canonicalUrl = $canonicalUrl;
    }

    /**
     * Добавляет данные в набор JSON+LD
     *
     * @param string $key
     * @param string|array $value
     */
    public function addMicroData(string $key, $value): void
    {
        if (is_scalar($value)) {
            $val = trim(html_entity_decode(strip_tags($value)));
        } elseif (is_array($value)) {
            $val = array_filter($value);
        }
        if (!empty($val)) {
            $this->microMarking[$key] = $val;
        }
    }

    /**
     * @return string
     */
    public function getMicroDataJSON(): ?string
    {
        $result = null;

        if (!empty($this->microMarking['@type'])) {
            $data = $this->microMarking;
            $data['@context'] = 'http://schema.org';
            ksort($data);
            $result = json_encode($data);
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $content
     */
    public function addCustomMeta(string $name, string $content): void
    {
        if ($content !== '') {
            $this->customTags[$name] = trim(html_entity_decode(strip_tags($content)));
        }
    }

    /**
     * Добавляет в разметку мета-теги OpenGraph
     *
     * @param OgType $ogType
     * @param string $value
     */
    public function addOGMeta(OgType $ogType, string $value): void
    {
        $this->addCustomMeta('og:' . $ogType->getValue(), $value);
    }

    public function getCustomMetas(): array
    {
        ksort($this->customTags);

        return array_filter($this->customTags);
    }

    /**
     * @return string|null
     */
    public function getRobotsIndexing(): ?string
    {
        return $this->robotsIndexing;
    }

    /**
     * @param string|null $robotsIndexing
     */
    public function setRobotsIndexing(?string $robotsIndexing): void
    {
        $this->robotsIndexing = $robotsIndexing;
    }
}
