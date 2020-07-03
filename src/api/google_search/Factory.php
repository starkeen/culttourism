<?php

declare(strict_types=1);

namespace app\api\google_search;

use GuzzleHttp\Client;

class Factory
{
    public static function build(): GoogleSearch
    {
        $guzzleClient = new Client();
        $plainClient = new PlainClient($guzzleClient, GOOGLE_CUSTOM_SEARCH_KEY, GOOGLE_CUSTOM_SEARCH_CX);
        $cachedClient = new CachedClient($plainClient);

        return new GoogleSearch($cachedClient);
    }

    public static function buildImageSearcher(): GoogleImageSearch
    {
        $basicSearcher = self::build();
        return new GoogleImageSearch($basicSearcher);
    }
}
