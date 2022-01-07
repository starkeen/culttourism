<?php

use app\crontab\CheckFilesDateCommand;

$sp = new MSysProperties($db);

$command = new CheckFilesDateCommand($sp);
$command->run();
