<?php

$checker = new DataChecker($db);
$log = $checker->repairPointsAddrs(30);

if (!empty($log)) {
    print_r($log);
}