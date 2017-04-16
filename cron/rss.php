<?php

use GuzzleHttp\Client;

$client = new Client(
    [
        'timeout' => 0,
    ]
);
$bitly = new Bitly($client);

$be = new MBlogEntries($db);
$entries = $be->getLastActive(5);

$gen = new RSSGenerator();
$gen->title = 'Культурный туризм в России';
$gen->link = _SITE_URL;
$gen->email = 'abuse@culttourism.ru';
$gen->description = 'Достопримечательности России и ближнего зарубежья: музеи, церкви и монастыри, памятники архитектуры';

$shorter = new RSSBitlyer($gen, $bitly);

$generators = [
    'blog.xml' => new RSSBitlyer($gen, $bitly),
    'blog-dlvrit.xml' => new RSSBitlyer($gen, $bitly),
    'blog-facebook.xml' => new RSSBitlyer($gen, $bitly),
    'blog-facebook-dev.xml' => new RSSBitlyer($gen, $bitly),
    'blog-twitter.xml' => new RSSBitlyer($gen, $bitly),
    'blog-telegram.xml' => new RSSBitlyer($gen, $bitly),
];

foreach ($generators as $fileType => $generator) {
    /** @var IRSSGenerator $generator */
    $fileName = sprintf('%s/feed/%s', _DIR_DATA, $fileType); // имя sitemap-файла
    $generator->url = sprintf('%sdata/feed/%s', _SITE_URL, $fileType); // URL файла

    file_put_contents($fileName, $generator->process($entries));
}
