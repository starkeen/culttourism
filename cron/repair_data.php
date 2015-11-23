<?php

$checker = new DataChecker($db);
$pc = new MPageCities($db);
$p = new MPagePoints($db);
$pt = new MPagePoints($db);
$ls = new MLists($db);
$bg = new MBlogEntries($db);

$log = $checker->repairPointsAddrs(30);
if (!empty($log)) {
    print_r($log);
}

$pt->repairPhones();

$pt->repairLinksAbsRel();
$pc->repairLinksAbsRel();
$ls->repairLinksAbsRel();
$bg->repairLinksAbsRel();
