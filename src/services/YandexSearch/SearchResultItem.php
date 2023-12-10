<?php

declare(strict_types=1);

namespace app\services\YandexSearch;

class SearchResultItem
{
    private string $title;

    private string $url;

    private string|null $snippet;

    public function __construct(string $title, string $url, ?string $snippet)
    {
        $this->title = $title;
        $this->url = $url;
        $this->snippet = $snippet;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getURL(): string
    {
        return $this->url;
    }

    public function getSnippet(): ?string
    {
        return $this->snippet;
    }
}
