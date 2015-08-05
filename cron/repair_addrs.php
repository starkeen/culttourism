<?php

$checker = new DataChecker($db);
$log = $checker->repairPointsAddrs(100);

if (!empty($log)) {
    print_r($log);
}