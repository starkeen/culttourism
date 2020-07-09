<?php

use app\checker\DataChecker;
use app\component\typograph\TypographFactory;

$checker = new DataChecker($db, TypographFactory::build());
$checker->resetOldData('pagepoints', 'pt_latitude', 2 * 30);

$pc = new MPageCities($db);
$pt = new MPagePoints($db);
$ls = new MLists($db);
$bg = new MBlogEntries($db);
$ca = new MCandidatePoints($db);

$log = [];
$log[] = $checker->repairPointsAddresses(30);
$log[] = $checker->repairPointsCoordinates(30);
$log[] = $checker->repairPointsPhones(50);
$log[] = $checker->repairCandidates(50);
$log[] = $checker->repairCandidatesAddresses(50);
$log[] = $checker->repairBlog(50);
$log[] = $checker->repairPoints(50);
$log[] = $checker->repairCity(50);
$logs = array_filter($log);
if (!empty($logs)) {
    print_r($logs);
}

$pt->repairData();
$ca->repairData();

$pt->repairLinksAbsRel();
$pc->repairLinksAbsRel();
$ls->repairLinksAbsRel();
$bg->repairLinksAbsRel();

$bg->detectPictures();
