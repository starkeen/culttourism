<?php

$dbau = $db->getTableName('authorizations');
$dbсс = $db->getTableName('curl_cache');

$tables_clean = array(
    array($db->getTableName('authorizations'), 'au_date_expire', 1),
    array($db->getTableName('news_items'), 'ni_pubdate', 3,),
    array($db->getTableName('log_actions'), 'la_date', 60,),
    array($db->getTableName('log_errors'), 'le_date', 30,),
);

$db->sql = "DELETE FROM $dbau WHERE au_service IN ('ajax', 'map')";
$db->exec();
$db->sql = "DELETE FROM $dbсс WHERE cc_expire < NOW()";
$db->exec();

foreach ($tables_clean as $i => $table) {
    $db->sql = "DELETE FROM {$table[0]}
                WHERE {$table[1]} < SUBDATE(NOW(), INTERVAL :interval DAY)";
    $db->execute(array(
        ':interval' => $table[2],
    ));
}

$tables_optimize = array(
    $db->getTableName('city_data'),
    $db->getTableName('pagepoints'),
    $db->getTableName('pagecity'),
    $db->getTableName('siteprorerties'),
    $db->getTableName('news_items'),
    $db->getTableName('wordstat'),
    $db->getTableName('candidate_points'),
);

foreach ($tables_optimize as $table) {
    $db->sql = "OPTIMIZE TABLE $table";
    $db->exec();
}