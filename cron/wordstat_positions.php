<?php

use app\crontab\WordstatPositionsCommand;

$ws = new MWordstat($db);

$command = new WordstatPositionsCommand($ws, $logger);
$command->run();
