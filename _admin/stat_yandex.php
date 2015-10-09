<?php

require_once('common.php');

$smarty->assign('title', 'Статистика Яндекса');

$sp = new MSysProperties($db);
$direct_apikey = $sp->getByName('key_direct_app');
$direct_password = $sp->getByName('pass_direct_app');
$api = new YandexDirectAPI($sp->getByName('token_direct'));
$ws = new MWordstat($db);

if (isset($_GET['code'])) {
    $newtoken = $api->getTokenConfirm($direct_apikey, $direct_password, $_GET['code']);
    $sp->updateByName('token_direct', $newtoken);
    header('Location: stat_yandex.php');
    exit();
}

if (isset($_POST) && !empty($_POST)) {
    if (isset($_POST['do_reload_stat'])) {
        $ws->resetWeightsAll();
    }
    if (isset($_POST['do_stack_empty'])) {
        foreach ($api->getReportsAll() as $rep) {
            $api->deleteReport($rep['ReportID']);
            $ws->resetWeightReport($rep['ReportID']);
        }
    }
    if (isset($_POST['do_delete_town'])) {
        $ws->deleteByPk($_POST['ws_id']);
    }
}

$new_reps_cnt = 5;
$reports = $api->getReportsAll();

$stat_dates = $ws->getStatDates();
$towns_seo_stat = $ws->getStatPositionsRanges();
$towns = array(
    'all' => $ws->getStatTowns(),
    'base' => $ws->getStatBase(),
    'remain' => $ws->getStatRemain(),
    'seo_worked' => $ws->getStatIndexed(),
    'seo_top_10' => $towns_seo_stat['10'],
    'seo_top_20' => $towns_seo_stat['20'],
    'seo_top_50' => $towns_seo_stat['50'],
    'seo_top_none' => $towns_seo_stat['none'],
    'date_positions' => $stat_dates['min_position'],
    'date_weights' => $stat_dates['min_weight'],
    'units' => $api->getClientUnits(),
);
$towns['worked'] = $towns['all'] - $towns['remain'];
$towns['seo_all'] = $towns['base'];

$smarty->assign('stat', $ws->getStatPopularity());
$smarty->assign('seo', $ws->getStatPositions());
$smarty->assign('reports', $reports);
$smarty->assign('towns', $towns);
$smarty->assign('direct_apikey', $direct_apikey);
$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/stat_yandex.sm.html'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit
();

