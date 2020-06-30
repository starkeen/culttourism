<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use GuzzleHttp\Client;

class Factory
{
    public static function build(): YandexSearchAPI
    {
        $client = new Client();
        $cachedClient = new CachedClient($client);
        $logger = new Logger();

        return new YandexSearchAPI($cachedClient, $logger, YANDEX_XML_USER, YANDEX_XML_KEY);
    }
}
