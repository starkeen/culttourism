<?php

declare(strict_types=1);

namespace app\api\google_search;

use GuzzleHttp\Client;

class Factory
{
    public static function build(): GoogleSearch
    {
        $client = self::getClient();

        return new GoogleSearch($client);
    }

    public static function buildImageSearcher(): GoogleImageSearch
    {
        $client = self::getClient();

        return new GoogleImageSearch($client);
    }

    private static function getClient(): HttpClientInterface
    {
        $guzzleClient = new Client();
        $plainClient = new PlainClient($guzzleClient, GOOGLE_CUSTOM_SEARCH_KEY, GOOGLE_CUSTOM_SEARCH_CX);
        return new CachedClient($plainClient);
    }
}
