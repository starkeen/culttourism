<?php

use app\api\YandexDirectAPI;

$sp = new MSysProperties($db);
$token_direct = $sp->getByName('app_direct_token');
$api = new YandexDirectAPI($token_direct);
$ws = new MWordstat($db);

//****************   1 - Обработка результатов запущеных ранее отчетов *********
$open_reports = $api->getReportsDone();

$reps = array();
$reps_to_reset = array();
$reports = $ws->getProcessingReports();
foreach ($reports as $row) {
    if (in_array($row['ws_rep_id'], $open_reports)) {
        $report_datas = $api->getReport($row['ws_rep_id']);
        $reps = array_merge($reps, $report_datas);
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
    $api->deleteReport($repdel);
}

//****************   5 - Постановка новых отчетов в очередь ********************
$new_reps_cnt = $api->getReportsCountRemain();
$units = $api->getClientUnits();

if ($units > 0 && $new_reps_cnt > 0) {
    for ($i = 1; $i <= $new_reps_cnt; $i++) {
        $portion = $ws->getPortionWeight(5);
        $_ids = array();
        $phrases = array();
        foreach ($portion as $row) {
            $phrases[] = $row['ws_city_title'] . ' достопримечательности';
            $_ids[] = $row['ws_id'];
        }
        $created_id = $api->createReport($phrases);
        if ($created_id) {
            $ws->setProcessingReport($_ids, $created_id);
        }
    }
}

