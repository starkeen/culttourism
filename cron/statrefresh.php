<?php

/*
 * Пересчет статистических данных по количеству городов и точек
 */
include _DIR_INCLUDES . '/class.Helper.php';
//echo '<p>statrefresh...';
$dbp = $db->getTableName('pagepoints');
$dbc = $db->getTableName('pagecity');
$dbs = $db->getTableName('siteprorerties');

/*
  $db->sql = "UPDATE $dbs SET sp_value = (SELECT count(pc_id) FROM $dbc) WHERE sp_id = 9"; //city statistics
  $db->exec();
  //echo '<br>city statistics ok';
  $db->sql = "UPDATE $dbs SET sp_value = (SELECT count(pt_id) FROM $dbp) WHERE sp_id = 10"; //point statistics
  $db->exec();
  //echo '<br>points statistics ok';
 */
$db->sql = "SELECT count(pc_id) AS cnt_pc FROM $dbc"; //city statistics
$db->exec();
$row1 = $db->fetch();

$db->sql = "SELECT count(pt_id) AS cnt_pt FROM $dbp"; //point statistics
$db->exec();
$row2 = $db->fetch();

//...о 8881 достопримечательностях в 283 городах и регионах
$text = $row2['cnt_pt'] . ' ' . Helper::getNumEnding($row2['cnt_pt'], array('достопримечательности', 'достопримечательностях', 'достопримечательностях'));
$text .= ' в ' . $row1['cnt_pc'] . ' ' . Helper::getNumEnding($row1['cnt_pc'], array('городе', 'городах', 'городах')) . ' и регионах';

$db->sql = "UPDATE $dbs SET sp_value = '$text' WHERE sp_id = 24"; //point statistics
  $db->exec();
?>
