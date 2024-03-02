<?php

use app\api\YandexDirectAPI;
use app\crontab\WordstatSuggestionsCommand;

$sp = new MSysProperties($db);
$tokenDirect = $sp->getByName('app_direct_token');
$api = new YandexDirectAPI($tokenDirect);
$ws = new MWordstat($db);

$command = new WordstatSuggestionsCommand($api, $ws);
$command->run();
