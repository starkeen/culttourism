<?php

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

$smarty->assign('title', 'Объекты в базе');

$dbpc = $db->getTableName('pagecity');
$dbpp = $db->getTableName('pagepoints');
$dbws = $db->getTableName('wordstat');
$dbrc = $db->getTableName('ref_city');
$dbrr = $db->getTableName('ref_region');
$dbco = $db->getTableName('ref_country');
$dbpt = $db->getTableName('ref_pointtypes');
$dbru = $db->getTableName('region_url');

$filter = array(
    'oid' => null,
    'country' => null,
    'region' => null,
    'city' => null,
    'type' => null,
    'addr' => null,
    'phone' => null,
    'web' => null,
    'gps' => array(
        'lat' => null,
        'lon' => null,
    ),
);
$refs = array(
    'countries' => array(),
    'regions' => array(),
    'cities' => array(),
    'types' => array(),
);

if (isset($_GET['oid']) && intval($_GET['oid']) > 0) {
    $filter['oid'] = intval($_GET['oid']);
}
if (isset($_GET['country']) && intval($_GET['country']) > 0) {
    $filter['country'] = intval($_GET['country']);
}
if (isset($_GET['region']) && intval($_GET['region']) > 0) {
    $filter['region'] = intval($_GET['region']);
}
if (isset($_GET['city']) && intval($_GET['city']) > 0) {
    $filter['city'] = intval($_GET['city']);
}
if (isset($_GET['addr']) && strlen($_GET['addr']) > 0) {
    $filter['addr'] = cut_trash_text($_GET['addr']);
}
if (isset($_GET['phone']) && strlen($_GET['phone']) > 0) {
    $filter['phone'] = cut_trash_text($_GET['phone']);
}
if (isset($_GET['web']) && strlen($_GET['web']) > 0) {
    $filter['web'] = cut_trash_text($_GET['web']);
}
if (isset($_GET['gps_lat']) && strlen($_GET['gps_lat']) > 0) {
    $filter['gps']['lat'] = cut_trash_float($_GET['gps_lat']);
}
if (isset($_GET['gps_lon']) && strlen($_GET['gps_lon']) > 0) {
    $filter['gps']['lon'] = cut_trash_float($_GET['gps_lon']);
}

$points = array();
$db->sql = "SELECT pp.*,
                pt.tp_icon,
                pc.pc_title, url.url,
                co.name AS country_name, rr.name AS region_name
            FROM $dbpp pp
                LEFT JOIN $dbpc pc ON pc.pc_id = pp.pt_citypage_id
                    LEFT JOIN $dbco co ON co.id = pc.pc_country_id
                    LEFT JOIN $dbrr rr ON rr.id = pc.pc_region_id
                    LEFT JOIN $dbru url ON url.uid = pc.pc_url_id
                LEFT JOIN $dbpt pt ON pt.tp_id = pp.pt_type_id
            WHERE 1\n";
if ($filter['oid'] > 0) {
    $db->sql .= "AND pp.pt_id = '{$filter['oid']}'\n";
}
if ($filter['country'] > 0) {
    $db->sql .= "AND pc.pc_country_id = '{$filter['country']}'\n";
}
if ($filter['region'] > 0) {
    $db->sql .= "AND pc.pc_region_id = '{$filter['region']}'\n";
}
if ($filter['city'] > 0) {
    $db->sql .= "AND pc.pc_city_id = '{$filter['city']}'\n";
}
if ($filter['addr'] != '') {
    $db->sql .= "AND pp.pt_adress LIKE '%{$filter['addr']}%'\n";
}
if ($filter['phone'] != '') {
    $db->sql .= "AND pp.pt_phone LIKE '%{$filter['phone']}%'\n";
}
if ($filter['web'] != '') {
    $db->sql .= "AND pp.pt_website LIKE '%{$filter['web']}%'\n";
}
if ($filter['gps']['lat'] != 0) {
    $db->sql .= "AND pp.pt_latitude >= '{$filter['gps']['lat']}'\n";
}
if ($filter['gps']['lon'] != 0) {
    $db->sql .= "AND pp.pt_longitude >= '{$filter['gps']['lon']}'\n";
}
$db->exec();
while ($row = $db->fetch()) {
    $points[] = $row;
}
$points_cnt = count($points);

$pager = new Pager($points);

$db->sql = "SELECT id, name AS title
            FROM $dbco WHERE id IN (SELECT pc_country_id FROM $dbpc)
            ORDER BY name";
$db->exec();
while ($row = $db->fetch()) {
    $refs['countries'][] = $row;
}
$db->sql = "SELECT id, name AS title
            FROM $dbrr
            WHERE id IN (SELECT pc_region_id FROM $dbpc)\n";
if ($filter['country'] > 0) {
    $db->sql .= "AND country_id = '{$filter['country']}'\n";
}
$db->sql .= "ORDER BY name";
$db->exec();
while ($row = $db->fetch()) {
    $refs['regions'][] = $row;
}
$db->sql = "SELECT id, name AS title
            FROM $dbrc
            WHERE id IN (SELECT pc_city_id FROM $dbpc)\n";
if ($filter['country'] > 0) {
    $db->sql .= "AND country_id = '{$filter['country']}'\n";
}
if ($filter['region'] > 0) {
    $db->sql .= "AND region_id = '{$filter['region']}'\n";
}
$db->sql .= "ORDER BY name";
$db->exec();
while ($row = $db->fetch()) {
    $refs['cities'][] = $row;
}
$db->sql = "SELECT tp_id AS id, tp_short AS title
            FROM $dbpt
            ORDER BY tr_order";
$db->exec();
while ($row = $db->fetch()) {
    $refs['types'][] = $row;
}

$smarty->assign('points', $pager->out);
$smarty->assign('pager', $pager->pages);
$smarty->assign('points_cnt', $points_cnt);
$smarty->assign('filter', $filter);
$smarty->assign('refs', $refs);

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/points.list.sm.html'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
