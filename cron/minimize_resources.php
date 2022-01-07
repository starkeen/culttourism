<?php

use app\crontab\MinimizeResourcesCommand;

$sp = new MSysProperties($db);

$command = new MinimizeResourcesCommand($sp);
$command->run();
