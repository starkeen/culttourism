<?php

declare(strict_types=1);

namespace app\api\google_search;

use app\api\google_search\constant\ImageColorType;
use app\api\google_search\constant\ImageSize;
use app\api\google_search\constant\ImageType;
use app\api\google_search\constant\SearchType;

class GoogleImageSearch extends GoogleSearch
{
    public function __construct(HttpClientInterface $httpClient)
    {
        parent::__construct($httpClient);
        $this->setOption('searchType', SearchType::IMAGE);
    }

    public function setImageType(ImageType $type): void
    {
        $this->setOption('imgType', $type->getValue());
    }

    public function setImageSize(ImageSize $size): void
    {
        $this->setOption('imgSize', $size->getValue());
    }

    public function setImageColorType(ImageColorType $colorType): void
    {
        $this->setOption('imgColorType', $colorType->getValue());
    }
}
