<?php

$checker = new DataChecker($db);
$log = $checker->repairPointsAddrs(50);

if (!empty($log)) {
    print_r($log);
}