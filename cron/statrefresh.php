<?php
/* 
 * Пересчет статистических данных по количеству городов и точек
 */

//echo '<p>statrefresh...';
$dbp = $db->getTableName('pagepoints');
$dbc = $db->getTableName('pagecity');
$dbs = $db->getTableName('siteprorerties');

$db->sql = "UPDATE $dbs SET sp_value = (SELECT count(pc_id) FROM $dbc) WHERE sp_id = 9"; //city statistics
$db->exec();
//echo '<br>city statistics ok';
$db->sql = "UPDATE $dbs SET sp_value = (SELECT count(pt_id) FROM $dbp) WHERE sp_id = 10"; //point statistics
$db->exec();
//echo '<br>points statistics ok';
?>
