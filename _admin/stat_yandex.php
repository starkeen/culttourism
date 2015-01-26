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
        /*
          $db->sql = "TRUNCATE TABLE $dbws";
          $db->exec();
          $db->sql = "INSERT INTO $dbws (ws_city_id, ws_city_title, ws_rep_id, ws_weight, ws_position, ws_position_date)
          (SELECT id, name, 0, -1, null, null
          FROM $dbrc rc
          WHERE rc.country_id IN (3159, 9908, 248, 1280, 2788, 245))";
          $db->exec();
         */
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

$towns = array(
    'all' => 0, 'base' => 0, 'worked' => 0, 'remain' => 0,
    'seo_all' => 0, 'seo_worked' => 0,
    'seo_top_10' => 0, 'seo_top_20' => 0, 'seo_top_50' => 0, 'seo_top_none' => 0,
    'date_positions' => '-', 'date_weights' => '-',
);

$db->sql = "SELECT count(*) AS cnt
            FROM $dbws ws";
$db->exec();
$row = $db->fetch();
$towns['all'] = $row['cnt'];

$db->sql = "SELECT count(*) AS cnt
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
            WHERE pc_id IS NOT NULL";
$db->exec();
$row = $db->fetch();
$towns['base'] = $row['cnt'];

$db->sql = "SELECT count(*) AS cnt
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
            WHERE pc_id IS NULL
                AND ws_weight = -1";
$db->exec();
$row = $db->fetch();
$towns['remain'] = $row['cnt'];
$towns['worked'] = $towns['all'] - $row['cnt'];

$db->sql = "SELECT count(*) AS cnt
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
            WHERE pc_id IS NOT NULL";
$db->exec();
$row = $db->fetch();
$towns['seo_all'] = $row['cnt'];
$db->sql = "SELECT count(*) AS cnt
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
            WHERE pc_id IS NOT NULL
                AND ws_position IS NOT NULL";
$db->exec();
$row = $db->fetch();
$towns['seo_worked'] = $row['cnt'];
$db->sql = "SELECT count(*) AS cnt, 
                IF(ws_position = 0, 0, IF(ws_position > 50, 0, IF(ws_position > 20, 50, IF(ws_position > 10, 20, 10)))) AS xtop
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
            WHERE pc_id IS NOT NULL
                AND ws_position IS NOT NULL
            GROUP BY xtop";
$db->exec();
while ($row = $db->fetch()) {
    if ($row['xtop'] == 0) {
        $towns['seo_top_none'] += $row['cnt'];
    }
    if ($row['xtop'] == 10) {
        $towns['seo_top_10'] += $row['cnt'];
    }
    if ($row['xtop'] == 20) {
        $towns['seo_top_20'] += $row['cnt'];
    }
    if ($row['xtop'] == 50) {
        $towns['seo_top_50'] += $row['cnt'];
    }
}

$db->sql = "SELECT DATE_FORMAT(MIN(ws_weight_date), '%d.%m.%Y') AS min_weight,
                DATE_FORMAT(MIN(ws_position_date), '%d.%m.%Y') AS min_position
            FROM $dbws ws";
$db->exec();
$row = $db->fetch();
$towns['date_weights'] = $row['min_weight'];
$towns['date_positions'] = $row['min_position'];



$db->sql = "SELECT ws_city_title AS city_name, rr.name AS region_name, co.name AS country_name,
                ws_city_id, ws_weight, ws.ws_weight_date,
                ws_weight_min, ws.ws_weight_min_date,
                ws_weight_max, ws.ws_weight_max_date,
                ROUND(100*(ws_weight_max - ws_weight) / ws_weight) AS weight_delta_max,
                ROUND(100*(ws_weight - ws_weight_min) / ws_weight) AS weight_delta_min,
                0 AS weight_delta_sign
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
                LEFT JOIN $dbrc rc ON rc.id = ws.ws_city_id
                    LEFT JOIN $dbrr rr ON rr.id = rc.region_id
                    LEFT JOIN $dbco co ON co.id = rc.country_id
            WHERE ws_weight > 0
                AND pc_id IS NULL
            ORDER BY ws_weight_min DESC, ws_weight_max DESC, ws_weight DESC
            LIMIT 50";
$db->exec();
$stat = array();
while ($row = $db->fetch()) {
    if ($row['weight_delta_max'] > $row['weight_delta_min'] && $row['weight_delta_min'] > 10) {
        $row['weight_delta_sign'] = -1;
    } elseif ($row['weight_delta_max'] < $row['weight_delta_min'] && $row['weight_delta_max'] > 10) {
        $row['weight_delta_sign'] = 1;
    } else {
        $row['weight_delta_sign'] = 0;
    }
    $stat[] = $row;
}

$db->sql = "SELECT ws_city_title AS city_name, rr.name AS region_name, co.name AS country_name,
                pc.pc_add_date, ws_city_id,
                ws_weight, ws.ws_weight_date,
                ws_position, ws.ws_position_date,
                ws_weight_max, ws.ws_weight_max_date,
                ws_weight_min, ws.ws_weight_min_date,
                ROUND(ws_weight_max/1000) AS weight_x1000,
                ROUND(ws_weight_max/100) AS weight_x100,
                ROUND(100*(ws_weight_max - ws_weight) / ws_weight) AS weight_delta_max,
                ROUND(100*(ws_weight - ws_weight_min) / ws_weight) AS weight_delta_min,
                0 AS weight_delta_sign,
                IF(ws_position = 0, 101, ws_position) AS ws_position_real,
                IF(ws_position = 0, '&mdash;', ws_position) AS ws_position,
                IF(ws_position = 0, 100, IF(ws_position > 50, 100, IF(ws_position > 20, 50, IF(ws_position > 10, 20, 10)))) AS position_x
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
                LEFT JOIN $dbrc rc ON rc.id = ws.ws_city_id
                    LEFT JOIN $dbrr rr ON rr.id = rc.region_id
                    LEFT JOIN $dbco co ON co.id = rc.country_id
            WHERE ws_weight > 0
                AND pc_id IS NOT NULL
                AND ws_position IS NOT NULL
                AND (ws_position > 10 OR ws_position = 0)
            GROUP BY city_name
            ORDER BY ws_weight_min DESC, ws_position_real DESC
            LIMIT 70";
//$db->showSQL();
$db->exec();
$seo = array();
while ($row = $db->fetch()) {
    if ($row['weight_delta_max'] > $row['weight_delta_min'] && $row['weight_delta_min'] > 10) {
        $row['weight_delta_sign'] = -1;
    } elseif ($row['weight_delta_max'] < $row['weight_delta_min'] && $row['weight_delta_max'] > 10) {
        $row['weight_delta_sign'] = 1;
    } else {
        $row['weight_delta_sign'] = 0;
    }
    $seo[] = $row;
}

$smarty->assign('stat', $stat);
$smarty->assign('seo', $seo);
$smarty->assign('reports', $reports);
$smarty->assign('towns', $towns);
$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/stat_yandex.sm.html'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit
();

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
