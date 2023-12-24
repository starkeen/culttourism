<?php

require_once 'common.php';

$smarty->assign('title', 'Точки без координат');

$dbpc = $db->getTableName('pagecity');
$dbpt = $db->getTableName('pagepoints');
$dbur = $db->getTableName('region_url');

$db->sql = "SELECT count(*) cnt,pt_citypage_id, pc.pc_title, url.url
            FROM $dbpt pp
            LEFT JOIN $dbpc pc ON pc.pc_id = pp.pt_citypage_id
            LEFT JOIN $dbur url ON url.uid = pc.pc_url_id
            WHERE `pt_longitude` IS NULL AND `pt_latitude` IS NULL
            GROUP BY `pt_citypage_id`
            ORDER BY cnt desc, pc_title
            LIMIT 30";
$db->exec();
$points = array();
$city_ids = array(0);
while ($row = $db->fetch()) {
    $city_ids[] = $row['pt_citypage_id'];
    $row['cnt_all'] = 0;
    $row['p'] = 0;
    $points[$row['pt_citypage_id']] = $row;
}
$city_list = implode(',', $city_ids);
$db->sql = "SELECT COUNT(*) AS cnt,pt_citypage_id FROM $dbpt WHERE pt_citypage_id IN ($city_list) GROUP BY pt_citypage_id";
$db->exec();
while ($row = $db->fetch()) {
    $points[$row['pt_citypage_id']]['cnt_all'] = $row['cnt'];
    $points[$row['pt_citypage_id']]['p'] = round(100 * $points[$row['pt_citypage_id']]['cnt'] / $row['cnt']);
}

$smarty->assign('points', $points);
$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/nogps.tpl'));

$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
