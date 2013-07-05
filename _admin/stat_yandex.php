<?php

require_once('common.php');

$smarty->assign('title', 'Статистика Яндекса');

$dbpc = $db->getTableName('pagecity');
$dbws = $db->getTableName('wordstat');
$dbrc = $db->getTableName('ref_city');
$dbrr = $db->getTableName('ref_region');
$dbco = $db->getTableName('ref_country');

if (isset($_POST) && !empty($_POST)) {
    if (isset($_POST['do_reload_full'])) {
        $db->sql = "TRUNCATE TABLE $dbws";
        $db->exec();
        $db->sql = "INSERT INTO $dbws (ws_city_id, ws_city_title, ws_rep_id, ws_weight, ws_position, ws_position_date)
                        (SELECT id, name, 0, -1, null, null
                         FROM $dbrc rc
                         WHERE rc.country_id IN (3159, 9908, 248))";
        $db->exec();
    }
    if (isset($_POST['do_reload_stat'])) {
        $db->sql = "UPDATE $dbws SET ws_weight = -1, ws_rep_id = 0";
        $db->exec();
    }
    if (isset($_POST['do_stack_empty'])) {
        $request = array(
            'method' => 'GetWordstatReportList',
        );
        $res = yandex_req($request);
        foreach ($res['data'] as $rep) {
            yandex_req(array(
                'method' => 'DeleteWordstatReport',
                'param' => $rep['ReportID'],
            ));
            $db->sql = "UPDATE $dbws SET ws_weight = -1 WHERE ws_rep_id = '{$rep['ReportID']}'";
            $db->exec();
        }
    }
    if (isset($_POST['do_delete_town'])) {
        $town_id = cut_trash_int($_POST['town_id']);
        $db->sql = "DELETE FROM $dbws WHERE ws_city_id = '$town_id'";
        $db->exec();
    }
}

$new_reps_cnt = 5;
$request = array(
    'method' => 'GetWordstatReportList',
);
$res = yandex_req($request);
$reports = array();
if (isset($res['data']) && !empty($res['data'])) {
    foreach ($res['data'] as $rep) {
        $reports[] = $rep;
    }
}

$towns = array('all' => 0, 'worked' => 0, 'remain' => 0);

$db->sql = "SELECT count(*) AS cnt
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
            WHERE pc_id IS NULL";
$db->exec();
$row = $db->fetch();
$towns['all'] = $row['cnt'];

$db->sql = "SELECT count(*) AS cnt
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
            WHERE pc_id IS NULL
                AND ws_weight = -1";
$db->exec();
$row = $db->fetch();
$towns['remain'] = $row['cnt'];
$towns['worked'] = $towns['all'] - $row['cnt'];


$db->sql = "SELECT rc.name AS city_name, rr.name AS region_name, co.name AS country_name, ws_city_id, ws_weight
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
                LEFT JOIN $dbrc rc ON rc.id = ws.ws_city_id
                    LEFT JOIN $dbrr rr ON rr.id = rc.region_id
                    LEFT JOIN $dbco co ON co.id = rc.country_id
            WHERE ws_weight > 0
                AND pc_id IS NULL
            ORDER BY ws_weight DESC
            LIMIT 50";
$db->exec();
$stat = array();
while ($row = $db->fetch()) {
    $stat[] = $row;
}
$smarty->assign('stat', $stat);
$smarty->assign('reports', $reports);
$smarty->assign('towns', $towns);
$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/stat_yandex.sm.html'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();

function yandex_req($request) {
    $url = "https://api.direct.yandex.ru/v4/json/";
    $request['locale'] = 'ru';
    $opts = array(
        'http' => array(
            'method' => "POST",
            'content' => json_encode($request),
        )
    );
    $context = stream_context_create($opts);
    stream_context_set_option($context, 'ssl', 'local_cert', _DIR_ROOT . '/data/private/api-yandex/solid-cert.crt');
    $result = @file_get_contents($url, 0, $context);

    return json_decode($result, true);
}

?>
