<?php

require_once('common.php');

$smarty->assign('title', 'Статистика Яндекса');

$sp = new MSysProperties($db);
$token_direct = $sp->getByName('token_direct');
$api = new YandexDirectAPI($token_direct);
$ws = new MWordstat($db);

if (isset($_POST) && !empty($_POST)) {
    if (isset($_POST['do_reload_stat'])) {
        $ws->resetWeightsAll();
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
            $ws->resetWeightReport($rep['ReportID']);
        }
    }
    if (isset($_POST['do_delete_town'])) {
        $ws->deleteTown($_POST['town_id']);
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

$towns['all'] = $ws->getStatTowns();
$towns['base'] = $ws->getStatBase();
$towns['remain'] = $ws->getStatRemain();
$towns['worked'] = $towns['all'] - $towns['remain'];

$towns['seo_all'] = $ws->getStatBase();
$towns['seo_worked'] = $ws->getStatIndexed();

$towns_seo_stat = $ws->getStatPositionsRanges();
$towns['seo_top_none'] = $towns_seo_stat['none'];
$towns['seo_top_10'] = $towns_seo_stat['10'];
$towns['seo_top_20'] = $towns_seo_stat['20'];
$towns['seo_top_50'] = $towns_seo_stat['50'];

$stat_dates = $ws->getStatDates();
$towns['date_weights'] = $stat_dates['min_weight'];
$towns['date_positions'] = $stat_dates['min_position'];

$smarty->assign('stat', $ws->getStatPopularity());
$smarty->assign('seo', $ws->getStatPositions());
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
