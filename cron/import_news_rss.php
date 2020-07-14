<?php

use app\crontab\ImportNewsCommand;

$ni = new MNewsItems($db);
$ns = new MNewsSources($db);

$command = new ImportNewsCommand($logger, $ni, $ns);
$command->run();
