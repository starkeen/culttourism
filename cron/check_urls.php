<?php

use app\crontab\CheckUrlsCommand;
use models\MLinks;

$linksModel = new MLinks($db);

$command = new CheckUrlsCommand($linksModel, $db, $logger);
$command->run();
