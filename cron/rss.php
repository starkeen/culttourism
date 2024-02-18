<?php

use app\crontab\RSSCommand;
use app\rss\RSSUrlShortener;
use app\rss\RSSGenerator;
use app\services\shortio\ShortIoClient;
use GuzzleHttp\Client;

$client = new Client(
    [
        'timeout' => 0,
    ]
);

$shortener = new ShortIoClient($client, 'go.culttourism.ru', SHORTIO_SECRET_KEY);

$be = new MBlogEntries($db);

$gen = new RSSGenerator();

$shorted = new RSSUrlShortener($gen, $shortener);

$command = new RSSCommand($gen, $be, $shorted);
$command->run();
