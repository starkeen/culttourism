<?php

error_reporting(E_ALL);

$pts = new MPagePoints($db);

foreach ($pts->getUnslug(60) as $point) {
    $pts->createSluglineById($point['pt_id']);
}

$check = $pts->checkSluglines();
if (!$check['state']) {
    echo "Errors in points //" . implode("\n", $check['errors']);
}
