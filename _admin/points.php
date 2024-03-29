<?php

use app\utils\JSON;

require_once '_common.php';
$smarty->assign('title', 'Объекты в базе');

$dbpc = $db->getTableName('pagecity');
$dbpp = $db->getTableName('pagepoints');
$dbws = $db->getTableName('wordstat');
$dbrc = $db->getTableName('ref_city');
$dbrr = $db->getTableName('ref_region');
$dbco = $db->getTableName('ref_country');
$dbpt = $db->getTableName('ref_pointtypes');
$dbru = $db->getTableName('region_url');

if (isset($_GET['act'])) {
    $data = [
        'state' => false,
        'out' => null,
    ];
    $oid = (int) $_GET['oid'];
    $prop = isset($_GET['prop']) ? $db->getEscapedString($_GET['prop']) : null;
    switch ($_GET['act']) {
        case 'getprop':
            $db->sql = "SELECT $prop FROM $dbpp WHERE pt_id = '$oid'";
            $db->exec();
            $row = $db->fetch();
            $data['out'] = $row[$prop];
            $data['state'] = true;
            break;
        case 'setprop':
            $val = $db->getEscapedString(trim($_POST['value']));
            if ($prop === 'pt_website' && strpos($val, 'http') !== 0) {
                $val = "http://$val";
            }
            $db->sql = "UPDATE $dbpp SET $prop = '$val', pt_lastup_date = now() WHERE pt_id = '$oid'";
            $db->exec();
            $data['out'] = $val;
            $data['state'] = true;
            break;
        case 'getcity':
            $db->sql = "SELECT pt_citypage_id FROM $dbpp WHERE pt_id = '$oid'";
            $db->exec();
            $row = $db->fetch();
            $citypage = $row['pt_citypage_id'];
            $data['out'] = [];
            $db->sql = "SELECT pc_id AS id, pc_title AS title, pc_region_id FROM $dbpc WHERE pc_id = '$citypage'";
            $db->exec();
            $row = $db->fetch();
            $data['out'][] = $row;
            $db->sql = "SELECT pc_id AS id, pc_title AS title FROM $dbpc WHERE pc_region_id = '{$row['pc_region_id']}' AND pc_id != '$citypage'";
            $db->exec();
            while ($row = $db->fetch()) {
                $data['out'][] = $row;
            }
            $data['state'] = true;
            break;
        default:
            throw new InvalidArgumentException('Ошибка роутинга');
    }
    JSON::echo($data);
}

$filter = [
    'oid' => null,
    'title' => null,
    'country' => null,
    'region' => null,
    'city' => null,
    'type' => null,
    'addr' => null,
    'noaddr' => null,
    'phone' => null,
    'web' => null,
    'gps' => [
        'lat' => null,
        'lon' => null,
    ],
];
$refs = [
    'countries' => [],
    'regions' => [],
    'cities' => [],
    'types' => [],
];

if (isset($_GET['oid']) && (int) $_GET['oid'] > 0) {
    $filter['oid'] = (int) $_GET['oid'];
}
if (isset($_GET['title']) && strlen($_GET['title']) > 0) {
    $filter['title'] = cut_trash_text($_GET['title']);
}
if (isset($_GET['country']) && (int) $_GET['country'] > 0) {
    $filter['country'] = (int) $_GET['country'];
}
if (isset($_GET['region']) && (int) $_GET['region'] > 0) {
    $filter['region'] = (int) $_GET['region'];
}
if (isset($_GET['city']) && (int) $_GET['city'] > 0) {
    $filter['city'] = (int) $_GET['city'];
}
if (isset($_GET['type']) && (int) $_GET['type'] > 0) {
    $filter['type'] = (int) $_GET['type'];
}
if (isset($_GET['addr']) && strlen($_GET['addr']) > 0) {
    $filter['addr'] = cut_trash_text($_GET['addr']);
}
if (isset($_GET['noaddr']) && (int) $_GET['noaddr'] === 1) {
    $filter['noaddr'] = 1;
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

$points = [];
$db->sql = "SELECT SQL_CALC_FOUND_ROWS
                pp.pt_id, pp.pt_name, pp.pt_slugline,
                pp.pt_adress, pp.pt_phone, pp.pt_website,
                pp.pt_latitude, pp.pt_longitude, pp.pt_deleted_at,
                CHAR_LENGTH(TRIM(pt_description)) AS descr_len,
                pt.tp_icon, pt.tp_short, pt.tp_name,
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
if ($filter['title'] != '') {
    $db->sql .= "AND pp.pt_name LIKE '%{$filter['title']}%'\n";
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
if ($filter['type'] > 0) {
    $db->sql .= "AND pp.pt_type_id = '{$filter['type']}'\n";
}
if ($filter['addr'] != '') {
    $db->sql .= "AND pp.pt_adress LIKE '%{$filter['addr']}%'\n";
}
if ($filter['noaddr'] == 1) {
    $db->sql .= "AND ABS(CHAR_LENGTH(pp.pt_adress)-CHAR_LENGTH(pc.pc_title)) < 6 AND pp.pt_latitude IS NOT NULL\n";
}
if ($filter['phone'] != '') {
    $db->sql .= "AND pp.pt_phone LIKE '%{$filter['phone']}%'\n";
}
if ($filter['web'] != '') {
    $db->sql .= "AND pp.pt_website LIKE '%{$filter['web']}%'\n";
}
if ($filter['gps']['lat'] != 0) {
    $lat_max = ($filter['gps']['lat']) + 0.5;
    $db->sql .= "AND pp.pt_latitude >= '{$filter['gps']['lat']}' AND pp.pt_latitude < '$lat_max'\n";
}
if ($filter['gps']['lon'] != 0) {
    $lon_max = ($filter['gps']['lon']) + 0.5;
    $db->sql .= "AND pp.pt_longitude >= '{$filter['gps']['lon']}' AND pp.pt_longitude < '$lon_max'\n";
}
$db->sql .= "ORDER BY pp.pt_create_date
            LIMIT 1000";

$db->exec();
$points = $db->fetchAll();

$db->sql = "SELECT FOUND_ROWS()";
$db->exec();
$points_cnt = $db->fetchCol();

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
$refs['regions'] = $db->fetchAll();

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
$refs['cities'] = $db->fetchAll();

$db->sql = "SELECT tp_id AS id, tp_short AS title
            FROM $dbpt
            ORDER BY tr_order";
$db->exec();
$refs['types'] = $db->fetchAll();

$smarty->assign('points', $pager->out);
$smarty->assign('pager', $pager->pages);
$smarty->assign('points_cnt', $points_cnt);
$smarty->assign('filter', $filter);
$smarty->assign('refs', $refs);

$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/points.list.tpl'));

$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
