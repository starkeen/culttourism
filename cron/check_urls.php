<?php

use app\crontab\CheckUrlsCommand;
use GuzzleHttp\Client;
use models\MLinks;

$linksModel = new MLinks($db);
$guzzle = new Client();

$command = new CheckUrlsCommand($linksModel, $guzzle);
$command->run();
