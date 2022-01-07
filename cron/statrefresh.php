<?php

use app\crontab\StatRefreshCommand;

$sp = new MSysProperties($db);
$pc = new MPageCities($db);
$pt = new MPagePoints($db);

$command = new StatRefreshCommand($pc, $pt, $sp);
$command->run();
