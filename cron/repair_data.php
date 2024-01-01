<?php

use app\checker\DataChecker;
use app\component\typograph\TypographFactory;
use app\crontab\RepairDataCommand;

$checker = new DataChecker($db, TypographFactory::build(), $app->getDadata());

$pc = new MPageCities($db);
$pt = new MPagePoints($db);
$ls = new MLists($db);
$bg = new MBlogEntries($db);
$ca = new MCandidatePoints($db);

$command = new RepairDataCommand($checker, $pt, $ca, $pc, $ls, $bg);
$command->run();
