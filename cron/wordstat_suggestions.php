<?php

$ws = new MWordstat($db);

$dbrc = $db->getTableName('ref_city');
$dbpc = $db->getTableName('pagecity');
$dbws = $db->getTableName('wordstat');


//****************   1 - Обработка результатов запущеных ранее отчетов *********
$request = array(
    'method' => 'GetWordstatReportList',
);
$res = yandex_req($request);
$open_reports = array();
if (isset($res['data']) && !empty($res['data'])) {
    foreach ($res['data'] as $rep) {
        if ($rep['StatusReport'] == 'Done') {
            $open_reports[] = $rep['ReportID'];
        }
    }
}
$db->sql = "SELECT ws_rep_id FROM $dbws
            WHERE ws_rep_id != 0
            GROUP BY ws_rep_id";
$db->exec();
$reps = array();
$reps_to_reset = array();
while ($row = $db->fetch()) {
    if (in_array($row['ws_rep_id'], $open_reports)) {
        $request = array(
            'method' => 'GetWordstatReport',
            'param' => $row['ws_rep_id'],
        );
        $res = yandex_req($request);
        if (isset($res['data'])) {
            foreach ($res['data'] as $data) {
                $rep = array('word' => $data['Phrase'], 'weight' => 0, 'rep_id' => $row['ws_rep_id']);
                foreach ($data['SearchedWith'] as $item) {
                    if ($item['Shows'] >= $rep['weight']) {
                        $rep['weight'] = $item['Shows'];
                    }
                }
                $reps[] = $rep;
            }
        } else {
            echo "Error2 in {$row['ws_rep_id']}:\n";
            print_r($res);
        }
    } else {
        $reps_to_reset[] = $row['ws_rep_id'];
    }
}

//****************   2 - Сброс очереди зависших отчетов ************************
if (!empty($reps_to_reset)) {
    $db->sql = "UPDATE $dbws
                SET ws_rep_id = 0
                WHERE ws_rep_id IN (" . implode(',', $reps_to_reset) . ")";
    $db->exec();
}

//****************   3 - Простановка данных по словам **************************
$reps_to_del = array();
foreach ($reps as $rep) {
    $city = trim(str_replace(' достопримечательности', '', $rep['word']));
    $weight = intval($rep['weight']);
    $repid = intval($rep['rep_id']);
    $db->sql = "UPDATE $dbws
                    SET ws_weight = '$weight', ws_weight_date = now(), ws_rep_id = 0
                WHERE ws_rep_id = '$repid'
                    AND ws_city_title = '$city'";
    $db->exec();
    $reps_to_del[$repid] = $repid;
}

$ws->updateMaxMin();

//****************   4 - Удаление отработанных отчетов *************************
foreach ($reps_to_del as $repdel) {
    yandex_req(array(
        'method' => 'DeleteWordstatReport',
        'param' => $repdel,
    ));
}

//****************   5 - Постановка новых отчетов в очередь ********************
$new_reps_cnt = 5;
$request = array(
    'method' => 'GetWordstatReportList',
);
$res = yandex_req($request);
if (isset($res['data']) && !empty($res['data'])) {
    foreach ($res['data'] as $rep) {
        $new_reps_cnt += -1;
    }
}

if ($new_reps_cnt > 0) {
    for ($i = 1; $i <= $new_reps_cnt; $i++) {
        $request = array(
            'method' => 'CreateNewWordstatReport',
            'param' => array(
                'Phrases' => array(),
                'GeoID' => array(0),
            ),
        );
        $portion = $ws->getPortionWeight(5);
        $_ids = array();
        foreach ($portion as $row) {
            $request['param']['Phrases'][] = iconv('ISO-8859-1', 'utf-8', $row['ws_city_title'] . ' достопримечательности');
            $_ids[] = $row['ws_id'];
        }
        $res = yandex_req($request);
        if (isset($res['data'])) {
            $res_id = intval($res['data']);
            $db->sql = "UPDATE $dbws SET ws_rep_id = '$res_id' WHERE ws_id IN (" . implode(', ', $_ids) . ")";
            $db->exec();
        } else {
            echo "Error1 (invalid sert?):\n";
            print_r($res);
        }
    }
}

//****************   6 - Оптимизация таблицы данных ****************************
$ws->optimize();

////////////////////////////////////////////////////////////////////////////////

function yandex_req($request) {
    //$url = "https://api-sandbox.direct.yandex.ru/json-api/v4/";
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
