<?php

$ws = new MWordstat($db);

//****************   1 - Обработка результатов запущеных ранее отчетов *********
$request_active = array(
    'method' => 'GetWordstatReportList',
);
$res_opened = yandex_req($request_active);
$open_reports = array();
if (isset($res_opened['data']) && !empty($res_opened['data'])) {
    foreach ($res_opened['data'] as $rep) {
        if ($rep['StatusReport'] == 'Done') {
            $open_reports[] = $rep['ReportID'];
        }
    }
}

$reps = array();
$reps_to_reset = array();
$reports = $ws->getProcessingReports();
foreach ($reports as $row) {
    if (in_array($row['ws_rep_id'], $open_reports)) {
        $request_report = array(
            'method' => 'GetWordstatReport',
            'param' => $row['ws_rep_id'],
        );
        $res_report = yandex_req($request_report);
        if (isset($res_report['data'])) {
            foreach ($res_report['data'] as $data) {
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
            print_r($res_report);
        }
    } else {
        $reps_to_reset[] = $row['ws_rep_id'];
    }
}

//****************   2 - Сброс очереди зависших отчетов ************************
if (!empty($reps_to_reset)) {
    $ws->resetQueue($reps_to_reset);
}

//****************   3 - Простановка полученных данных по словам **************************
$reps_to_del = array();
foreach ($reps as $rep) {
    $city = trim(str_replace('достопримечательности', '', $rep['word']));
    $repid = intval($rep['rep_id']);
    $ws->setWeight($repid, $city, intval($rep['weight']));
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
$request_count = array(
    'method' => 'GetWordstatReportList',
);
$res_count = yandex_req($request_count);
if (isset($res_count['data']) && !empty($res_count['data'])) {
    foreach ($res_count['data'] as $rep) {
        $new_reps_cnt += -1;
    }
}
echo 'new reps: ', $new_reps_cnt, PHP_EOL;
if ($new_reps_cnt > 0) {
    for ($i = 1; $i <= $new_reps_cnt; $i++) {
        $request_create = array(
            'method' => 'CreateNewWordstatReport',
            'param' => array(
                'Phrases' => array(),
                'GeoID' => array(0),
            ),
        );
        $portion = $ws->getPortionWeight(5);
        $_ids = array();
        foreach ($portion as $row) {
            $request_create['param']['Phrases'][] = iconv('ISO-8859-1', 'utf-8', $row['ws_city_title'] . ' достопримечательности');
            $_ids[] = $row['ws_id'];
            echo 'added: ', $row['ws_city_title'], PHP_EOL;
        }
        $res_create = yandex_req($request_create);
        if (isset($res_create['data'])) {
            $ws->setProcessingReport($_ids, intval($res_create['data']));
        } else {
            echo "Error1 (invalid sert?):\n";
            print_r($res_create);
        }
    }
}

//****************   6 - Оптимизация таблицы данных ****************************
$ws->optimize();

////////////////////////////////////////////////////////////////////////////////

/**
 * Запрос к Яндексу
 * @param array $request
 * @return array
 */
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
