<?php

/*
 * Пересчет статистических данных по количеству городов и точек
 */

$dbp = $db->getTableName('pagepoints');
$dbc = $db->getTableName('pagecity');

$sp = new MSysProperties($db);

$db->sql = "SELECT count(pc_id) AS cnt_pc FROM $dbc"; //city statistics
$db->exec();
$row1 = $db->fetch();

$db->sql = "SELECT count(pt_id) AS cnt_pt FROM $dbp"; //point statistics
$db->exec();
$row2 = $db->fetch();

//...о 8881 достопримечательностях в 283 городах и регионах
$text = $row2['cnt_pt'] . ' ' . Helper::getNumEnding($row2['cnt_pt'], array('достопримечательности', 'достопримечательностях', 'достопримечательностях'));
$text .= ' в ' . $row1['cnt_pc'] . ' ' . Helper::getNumEnding($row1['cnt_pc'], array('городе', 'городах', 'городах')) . ' и регионах';

$sp->updateById(24, array('sp_value' => $text));

