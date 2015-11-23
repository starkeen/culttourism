<?php

$checker = new DataChecker($db);
$pc = new MPageCities($db);
$p = new MPagePoints($db);
$pt = new MPagePoints($db);
$ls = new MLists($db);
$bg = new MBlogEntries($db);
$ca = new MCandidatePoints($db);

$log = $checker->repairPointsAddrs(30);
if (!empty($log)) {
    print_r($log);
}

$pt->repairData();
$ca->repairData();

$pt->repairLinksAbsRel();
$pc->repairLinksAbsRel();
$ls->repairLinksAbsRel();
$bg->repairLinksAbsRel();
