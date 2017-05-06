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

$bitlyed = new RSSBitlyer($gen, $bitly);

$generators = [
    'blog.xml' => new RSSAddUTM($bitlyed, 'feedburner'),
    'blog-dlvrit.xml' => new RSSAddUTM($bitlyed, 'dlvrit'),
    'blog-facebook.xml' => new RSSAddUTM(new RSSInstantArticler($bitlyed), 'facebook'),
    'blog-facebook-dev.xml' => new RSSAddUTM(new RSSInstantArticler($bitlyed), 'facebook'),
    'blog-facebook-ifttt.xml' => new RSSAddUTM(new RSSInstantArticler($bitlyed), 'facebook'),
    'blog-twitter.xml' => new RSSAddUTM($bitlyed, 'twitter'),
    'blog-telegram.xml' => new RSSAddUTM($bitlyed, 'telegram'),
];

foreach ($generators as $fileType => $generator) {
    /** @var IRSSGenerator $generator */
    $fileName = sprintf('%s/feed/%s', _DIR_DATA, $fileType); // имя sitemap-файла
    $generator->url = sprintf('%sdata/feed/%s', _SITE_URL, $fileType); // URL файла

    file_put_contents($fileName, $generator->process($entries));
}
