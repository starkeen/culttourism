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
    private array $titleElements = [];

    /**
     * @var string[]
     */
    private array $keywordsElements = [];

    /**
     * @var string[]
     */
    private array $descriptionElements = [];

    /**
     * @var string
     */
    private string $titleDelimiter = ' - ';

    /**
     * @var string|null
     */
    private ?string $canonicalUrl;

    /**
     * @var array
     */
    private array $microMarking = [];

    /**
     * @var array
     */
    private array $breadcrumbsMarking = [];

    /**
     * @var array
     */
    private array $customTags = [];

    /**
     * @var string
     */
    private string $robotsIndexing = 'index,follow';

    private ?string $yandexMapsKey = null;

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
    public function addMainMicroData(string $key, $value): void
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
    public function getMainMicroDataJSON(): ?string
    {
        $result = null;

        if (!empty($this->microMarking['@type'])) {
            $data = $this->microMarking;
            $data['@context'] = 'https://schema.org';
            ksort($data);
            $result = json_encode($data, JSON_THROW_ON_ERROR);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getWebsiteMicroDataJSON(): ?string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => 'https://culttourism.ru/',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => 'https://culttourism.ru/search/?&q={query}',
                'query' => 'required',
                'query-input' => 'required name=query',
            ],
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function addBreadcrumb(string $title, string $url): void
    {
        $this->breadcrumbsMarking[] = [
            'title' => $title,
            'url' => $url,
        ];
    }

    /**
     * @return string
     */
    public function getBreadcrumbsMicroDataJSON(): ?string
    {
        $result = null;

        if (!empty($this->breadcrumbsMarking)) {
            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [],
            ];
            $position = 1;
            foreach ($this->breadcrumbsMarking as $pageItem) {
                $data['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'item' => Urls::getAbsoluteURL($pageItem['url']),
                    'name' => $pageItem['title'],
                ];
            }
            $result = json_encode($data, JSON_THROW_ON_ERROR);
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

    public function getOGMeta(OgType $ogType): ?string
    {
        $tagName = 'og:' . $ogType->getValue();

        return $this->customTags[$tagName] ?? null;
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
