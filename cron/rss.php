<?php

use app\rss\IRSSGenerator;
use app\rss\RSSAddUTM;
use app\rss\RSSBitlyer;
use app\rss\RSSGenerator;
use app\rss\RSSInstantArticler;
use GuzzleHttp\Client;

$client = new Client(
    [
        'timeout' => 0,
    ]
);
$curlCache = new MCurlCache($db);
$bitly = new Bitly($client, $curlCache);

$be = new MBlogEntries($db);
$entries = $be->getLastActive(5);

$gen = new RSSGenerator();
$gen->title = 'Культурный туризм в России';
$gen->link = _SITE_URL;
$gen->email = 'abuse@culttourism.ru';
$gen->description = 'Достопримечательности России и ближнего зарубежья: музеи, церкви и монастыри, памятники архитектуры';

$shorter = new RSSBitlyer($gen, $bitly);

$generators = [
    'blog.xml' => new RSSBitlyer(new RSSAddUTM($gen), $bitly),
    'blog-dlvrit.xml' => new RSSBitlyer(new RSSAddUTM($gen, 'dlvrit'), $bitly),
    'blog-facebook.xml' => new RSSBitlyer(new RSSAddUTM(new RSSInstantArticler($gen), 'facebook'), $bitly),
    'blog-facebook-dev.xml' => new RSSBitlyer(new RSSAddUTM(new RSSInstantArticler($gen), 'facebook'), $bitly),
    'blog-twitter.xml' => new RSSBitlyer(new RSSAddUTM($gen, 'twitter'), $bitly),
    'blog-telegram.xml' => new RSSBitlyer(new RSSAddUTM($gen, 'telegram'), $bitly),
];

foreach ($generators as $fileType => $generator) {
    /** @var IRSSGenerator $generator */
    $fileName = sprintf('%s/feed/%s', _DIR_DATA, $fileType); // имя sitemap-файла
    $generator->url = sprintf('%sdata/feed/%s', _SITE_URL, $fileType); // URL файла

    file_put_contents($fileName, $generator->process($entries));
}
