<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use app\db\FactoryDB;
use GuzzleHttp\Client;
use MSearchLog;

class Factory
{
    public static function build(): YandexSearchAPI
    {
        $db = FactoryDB::db();
        $searchLogModel = new MSearchLog($db);
        $client = new Client();
        $plainClient = new PlainClient($client, YANDEX_XML_USER, YANDEX_XML_KEY);
        $cachedClient = new CachedClient($plainClient, $searchLogModel);

        return new YandexSearchAPI($cachedClient);
    }
}
