<?php

use app\crontab\RankerCommand;

$command = new RankerCommand($db);
$command->run();
