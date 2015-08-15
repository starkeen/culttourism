<?php

$cc = new MCurlCache($db);
$cc->cleanExpired();

$au = new MAuthorizations($db);
$au->cleanExpired();
$au->cleanUnused();

$la = new MLogActions($db);
$la->cleanExpired();

$le = new MLogErrors($db);
$le->cleanExpired();

$tables_clean = array(
    array($db->getTableName('news_items'), 'ni_pubdate', 3,),
);

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