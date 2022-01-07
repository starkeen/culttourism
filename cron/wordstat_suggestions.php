<?php

use app\api\YandexDirectAPI;
use app\crontab\WordstatSuggestionsCommand;

$sp = new MSysProperties($db);
$token_direct = $sp->getByName('app_direct_token');
$api = new YandexDirectAPI($token_direct);
$ws = new MWordstat($db);


$command = new WordstatSuggestionsCommand($api, $ws);
$command->run();
