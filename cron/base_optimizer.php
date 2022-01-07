<?php

use app\crontab\BaseOptimizerCommand;

$cc = new MCurlCache($db);
$au = new MAuthorizations($db);
$la = new MLogActions($db);
$le = new MLogErrors($db);
$ni = new MNewsItems($db);

$command = new BaseOptimizerCommand($cc, $au, $la, $le, $ni);
$command->run();
