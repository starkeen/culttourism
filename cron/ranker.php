<?php

//echo '<p>ranker...';
$dbp = $db->getTableName('pagepoints');
$dbc = $db->getTableName('pagecity');
$dbsp = $db->getTableName('statpoints');
$dbsc = $db->getTableName('statcity');

$db->sql = "UPDATE $dbp pp SET pp.pt_cnt_shows = pp.pt_cnt_shows + 
            (SELECT count(sp.sp_id) as cnt FROM $dbsp sp WHERE sp.sp_pagepoint_id = pp.pt_id)";
$db->exec();
$db->sql = "TRUNCATE TABLE $dbsp";
$db->exec();

$db->sql = "UPDATE $dbp pp SET pp.pt_rank = 1000*pp.pt_cnt_shows/(DATEDIFF(now(),pp.pt_create_date)+1) + 100 * pp.pt_is_best";
$db->exec();

$db->sql = "UPDATE $dbc pc SET pc.pc_cnt_shows = pc.pc_cnt_shows + 
            (SELECT 100*count(sc.sc_id) as cnt FROM $dbsc sc WHERE sc.sc_citypage_id = pc.pc_id)";
$db->exec();
$db->sql = "TRUNCATE TABLE $dbsc";
$db->exec();

$db->sql = "UPDATE $dbc pc SET pc.pc_rank = 100*pc.pc_cnt_shows/(DATEDIFF(now(),pc.pc_add_date)+1)";
$db->exec();
?>
