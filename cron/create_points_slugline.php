<?php

error_reporting(E_ALL);

$pts = new Points($db);

foreach ($pts->getUnslug() as $point) {
    $pts->createSluglineById($point['pt_id']);
}

$check = $pts->checkSluglines();
if (!$check['state']) {
    echo "Errors in points //" . implode("\n", $check['errors']);
}
