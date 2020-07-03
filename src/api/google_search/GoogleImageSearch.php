<?php

declare(strict_types=1);

namespace app\api\google_search;

class GoogleImageSearch extends GoogleSearch
{
    public function __construct(HttpClientInterface $httpClient)
    {
        parent::__construct($httpClient);
        $this->setOption('searchType', 'image');
    }

    public function setImageType(string $type): void
    {
        $this->setOption('imgType', $type);
    }

    public function setImageSize(string $size): void
    {
        $this->setOption('imgSize', $size);
    }

    public function setImageColorType(string $colorType): void
    {
        $this->setOption('imgColorType', $colorType);
    }
}
