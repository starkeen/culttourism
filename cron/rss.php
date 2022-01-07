<?php

use app\crontab\RSSCommand;
use app\includes\Bitly;
use app\rss\RSSBitlyer;
use app\rss\RSSGenerator;
use GuzzleHttp\Client;

$client = new Client(
    [
        'timeout' => 0,
    ]
);
$curlCache = new MCurlCache($db);
$bitly = new Bitly($client, $curlCache);

$be = new MBlogEntries($db);

$gen = new RSSGenerator();

$bitlyed = new RSSBitlyer($this->generator, $bitly);

$command = new RSSCommand($gen, $be, $bitlyed);
$command->run();
