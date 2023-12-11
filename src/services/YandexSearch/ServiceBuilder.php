<?php

declare(strict_types=1);

namespace app\services\YandexSearch;

use GuzzleHttp\Client;

class ServiceBuilder
{
    public static function build(): YandexSearchService
    {
        $guzzleClient = new Client();

        return new YandexSearchService($guzzleClient, YANDEX_SEARCH_ID, YANDEX_SEARCH_KEY);
    }
}
