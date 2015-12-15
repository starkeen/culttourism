<?php

$checker = new DataChecker($db);
$pc = new MPageCities($db);
$p = new MPagePoints($db);
$pt = new MPagePoints($db);
$ls = new MLists($db);
$bg = new MBlogEntries($db);
$ca = new MCandidatePoints($db);

$log = array();
$log[] = $checker->repairPointsAddrs(30);
$log[] = $checker->repairCandidates(50);
$log[] = $checker->repairBlog(10);
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
