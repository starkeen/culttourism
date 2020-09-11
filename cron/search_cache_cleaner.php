<?php

declare(strict_types=1);

use app\crontab\CleanSearchCacheCommand;

$cacheModel = new MSearchLog($db);

$command = new CleanSearchCacheCommand($cacheModel);
$command->run();
