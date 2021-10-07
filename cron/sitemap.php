<?php

use app\crontab\SitemapCommand;

$command = new SitemapCommand($db, $smarty);
$command->run();
