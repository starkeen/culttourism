<?php

use app\crontab\CleanStaticFilesCommand;

$command = new CleanStaticFilesCommand();
$command->run();
