<?php

use app\crontab\BreadcrumbsCommand;

$command = new BreadcrumbsCommand(new MPageCities($db));
$command->run();
