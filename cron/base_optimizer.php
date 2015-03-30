<?php

$tables = array(
    $db->getTableName('city_data'),
    $db->getTableName('pagepoints'),
    $db->getTableName('pagecity'),
    $db->getTableName('statpoints'),
    $db->getTableName('statcity'),
    $db->getTableName('siteprorerties'),
    $db->getTableName('news_items'),
    $db->getTableName('wordstat'),
    $db->getTableName('candidate_points'),
);

foreach ($tables as $table) {
    $db->sql = "OPTIMIZE TABLE $table";
    $db->exec();
}