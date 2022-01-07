<?php

use app\crontab\SearchStatCommand;

$command = new SearchStatCommand($db);
$command->run();
