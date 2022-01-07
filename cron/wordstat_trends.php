<?php

use app\crontab\WordstatTrendsCommand;


$ws = new MWordstatTrends($db);

$command = new WordstatTrendsCommand($ws);
$command->run();
