<?php

declare(strict_types=1);

use app\db\FactoryDB;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use GuzzleHttp\Psr7\Uri;

error_reporting(E_ALL);

/* Общие функции и опции */
require_once('../config/configuration.php');
include _DIR_ROOT . '/vendor/autoload.php';


$db = FactoryDB::db();
$pc = new MPageCities($db);
$pt = new MPagePoints($db);
$client = new Client(['base_uri' => 'https://culttourism.ru']);

$pattern = '/.*href="(.*\/object[0-9]*\.html)".*/i';

echo '<p>РЕГИОНЫ';

$db->sql = "SELECT pc_id, pc_title, pc_text FROM cult_pagecity WHERE pc_text LIKE '%object%' LIMIT 10";
$db->exec();
$pages = $db->fetchAll();
foreach ($pages as $page) {
    $matches = [];
    $replaceFrom = [];
    $replaceTo = [];

    echo '<p>', $page['pc_title'], ': ';

    if (preg_match_all($pattern, $page['pc_text'], $matches)) {
        foreach ((array) $matches[1] as $link) {
            $replaceFrom[] = $link;
            echo $link, ' => ';

            /** @var Uri $url */
            $url = null;
            $response = $client->get($link, [
                'on_stats' => function (TransferStats $stats) use (&$url) {
                    $url = $stats->getEffectiveUri();
                }
            ]);
            $canonical = $url->getPath();
            echo $canonical;
            $replaceTo[] = $canonical;
        }
    }

    if (count($replaceFrom) !== count($replaceTo)) {
        echo '<br>Ошибка в ссылках!';
    }

    $corrected = str_replace($replaceFrom, $replaceTo, $page['pc_text']);
    $pc->updateByPk($page['pc_id'], ['pc_text' => $corrected]);
}

echo '<p>ТОЧКИ';

$db->sql = "SELECT pt_id, pt_name, pt_description FROM cult_pagepoints WHERE pt_description LIKE '%object%' LIMIT 10";
$db->exec();
$pages = $db->fetchAll();
foreach ($pages as $page) {
    $matches = [];
    $replaceFrom = [];
    $replaceTo = [];


    echo '<p>', $page['pt_name'], ': ';

    if (preg_match_all($pattern, $page['pt_description'], $matches)) {
        foreach ((array) $matches[1] as $link) {
            $replaceFrom[] = $link;
            echo $link, ' => ';

            /** @var Uri $url */
            $url = null;
            $response = $client->get($link, [
                'on_stats' => function (TransferStats $stats) use (&$url) {
                    $url = $stats->getEffectiveUri();
                }
            ]);
            $canonical = $url->getPath();
            echo $canonical;
            $replaceTo[] = $canonical;
        }
    }

    if (count($replaceFrom) !== count($replaceTo)) {
        echo '<br>Ошибка в ссылках!';
    }

    $corrected = str_replace($replaceFrom, $replaceTo, $page['pt_description']);
    $pt->updateByPk($page['pt_id'], ['pt_description' => $corrected]);
}
