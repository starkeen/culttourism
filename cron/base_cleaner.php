<?php

$dbau = $db->getTableName('authorizations');

$tables = array(
    array($db->getTableName('authorizations'), 'au_date_expire', 7),
    array($db->getTableName('news_items'), 'ni_pubdate', 3,),
    array($db->getTableName('log_actions'), 'la_date', 60),
    array($db->getTableName('log_errors'), 'le_date', 30),
);

$db->sql = "DELETE FROM $dbau WHERE au_service = 'ajax')";
$db->exec();

foreach ($tables as $table) {
    $db->sql = "DELETE FROM {$table[0]}
                WHERE {$table[1]} < SUBDATE(NOW(), INTERVAL {$table[2]} DAY)"
            . (isset($table[3]) ? " AND {$table[3]}" : '');
    $db->exec();
    $db->sql = "OPTIMIZE TABLE {$table[0]}";
    $db->exec();
}
?>
