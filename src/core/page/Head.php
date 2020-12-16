<?php

declare(strict_types=1);

namespace app\core\page;

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
        return $this->canonicalUrl !== null ? rtrim(_SITE_URL, '/') . $this->canonicalUrl : null;
    }

    /**
     * @param string $canonicalUrl
     */
    public function setCanonicalUrl(string $canonicalUrl): void
    {
        $this->canonicalUrl = $canonicalUrl;
    }
}
