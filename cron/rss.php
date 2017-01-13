<?php

$filesRSS = [
    'blog.xml',
    'blog-dlvrit.xml',
    'blog-facebook.xml',
    'blog-facebook-dev.xml',
    'blog-twitter.xml',
    'blog-telegram.xml',
];

$be = new MBlogEntries($db);
$entries = $be->getLastActive(10);

$gen = new RSSGenerator();
$gen->title = 'Культурный туризм в России';
$gen->link = _SITE_URL;
$gen->email = 'abuse@culttourism.ru';
$gen->description =  'Достопримечательности России и ближнего зарубежья: музеи, церкви и монастыри, памятники архитектуры';

$client = new \GuzzleHttp\Client([
    'timeout' => 0,
]);
$bitly = new Bitly($client);
$shorter = new RSSBitlyer($gen, $bitly);

foreach ($filesRSS as $fileType) {
    $fileName = sprintf('%s/feed/%s', _DIR_DATA, $fileType); //имя sitemap-файла
    $gen->url = sprintf('%sdata/feed/%s', _SITE_URL, $fileType);
    file_put_contents($fileName, $shorter->process($entries));
}
