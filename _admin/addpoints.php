<?php

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

$smarty->assign('title', 'Заявки на добавление');

$c = new MCandidatePoints($db);

if (isset($_GET['id']) && isset($_GET['act'])) {
    $out = array('state' => false, 'id' => intval($_GET['id']), 'data' => null, 'error' => null);
    switch ($_GET['act']) {
        case "set_type":
            $out['state'] = $c->updateByPk($out['id'], array(
                'cp_type_id' => intval($_GET['ptype']),
                'cp_state' => 25,
            ));
            break;
        case "get_analogs":
            $ys = new YandexSearcher();
            $ys->setPagesMax(10);
            $ys->enableLogging($db);
            $res = $ys->search($_GET['pname'] . " host:" . _URL_ROOT, $db);
            $out['founded'] = $res['results'];
            $out['error'] = $res['error_text'];
            $out['state'] = $res['error_code'] == 0;
            break;
        case "citysuggest":
            $pc = new MPageCities($db);
            $out['query'] = htmlentities(cut_trash_string($_GET['query']), ENT_QUOTES, "UTF-8");
            $out['suggestions'] = array();
            $variants = $pc->getSuggestion($out['query']);
            foreach ($variants as $variant) {
                $out['suggestions'][] = array(
                    'value' => "{$variant['pc_title']}",
                    'pcid' => "{$variant['pc_id']}",
                    'url' => "{$variant['url']}/",
                );
            }
            break;
        case "set_citypage":
            $out['state'] = $c->updateByPk($out['id'], array(
                'cp_citypage_id' => intval($_GET['pc_id']),
                'cp_state' => 25,
            ));
            break;
        case "get_citypage":
            $pc = new MPageCities($db);
            $out['citypage'] = $pc->getItemByPk(intval($_GET['pc_id']));
            $out['state'] = true;
            break;
        case "save_candidate":
            $out['state'] = $c->updateByPk($out['id'], array(
                'cp_title' => trim($_POST['title']),
                'cp_text' => trim($_POST['text']),
                'cp_addr' => trim($_POST['addr']),
                'cp_phone' => trim($_POST['phone']),
                'cp_worktime' => trim($_POST['worktime']),
                'cp_web' => trim($_POST['web']),
                'cp_latitude' => cut_trash_float($_POST['lat']),
                'cp_longitude' => cut_trash_float($_POST['lon']),
                'cp_zoom' => cut_trash_float($_POST['zoom']),
                'cp_state' => intval($_POST['state_id']),
            ));
            break;
        case "set_ignore":
            $out['state'] = $c->updateByPk($out['id'], array(
                'cp_state' => intval($_GET['state_id']),
                'cp_active' => 0,
            ));
            break;
        case "move":
            $pt = new MPagePoints($db);
            $candidate = $c->getItemByPk($out['id']);
            if (mb_strlen($candidate['cp_title'], 'utf-8') <= 4) {
                $out['error'][] = 'Слишком короткое название (минимум 4 символа)';
            }
            if ($candidate['cp_citypage_id'] == 0) {
                $out['error'][] = 'Не указана страница назначения';
            }
            if ($candidate['cp_type_id'] == 0) {
                $out['error'][] = 'Не указан тип';
            }
            if (empty($out['error'])) {
                $new_id = $pt->insert(array(
                    'pt_name' => $candidate['cp_title'],
                    'pt_description' => $candidate['cp_text'],
                    'pt_citypage_id' => $candidate['cp_citypage_id'],
                    'pt_latitude' => $candidate['cp_latitude'],
                    'pt_longitude' => $candidate['cp_longitude'],
                    'pt_latlon_zoom' => $candidate['cp_zoom'],
                    'pt_type_id' => $candidate['cp_type_id'],
                    'pt_website' => $candidate['cp_web'],
                    'pt_worktime' => $candidate['cp_worktime'],
                    'pt_adress' => $candidate['cp_addr'],
                    'pt_phone' => $candidate['cp_phone'],
                    'pt_email' => $candidate['cp_email'],
                    'pt_is_best' => 0,
                    'pt_active' => 1,
                ));
                $out['state'] = $c->updateByPk($out['id'], array(
                    'cp_state' => 6,
                    'cp_point_id' => $new_id,
                    'cp_active' => 0,
                ));
            }
            break;
    }
    $out['data'] = $c->getItemByPk($out['id']);
    header("Content-type: text/json");
    echo json_encode($out);
    exit();
} elseif (isset($_GET['id']) && !isset($_GET['act'])) {
    $rpt = new MRefPointtypes($db);

    $item = $c->getItemByPk(intval($_GET['id']));

    $smarty->assign('claim', $item);
    $smarty->assign('referer', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'addpoints.php');
    $smarty->assign('ref_types', $rpt->getActive());
    // -----------   обработка заявки ----------
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/pointadding.item.sm.html'));
} else {
    // -----------   список активных ----------
    $pt = new MRefPointtypes($db);
    $uv_st = new UnirefValues($db, 3);

    $filter = array(
        'type' => isset($_GET['type']) ? intval($_GET['type']) : 0,
        'pcid' => isset($_GET['pcid']) ? intval($_GET['pcid']) : 0,
        'state' => isset($_GET['state']) ? intval($_GET['state']) : 0,
        'gps' => isset($_GET['gps']) ? intval($_GET['gps']) : 0,
    );

    $list = $c->getByFilter($filter);
    $ref_pc = array();
    foreach ($list as $li) {
        $ref_pc[$li['cp_citypage_id']] = array('id' => $li['cp_citypage_id'], 'title' => $li['page_title'] ? $li['page_title'] : '-не указано-');
    }
    asort($ref_pc);

    $matrix = $c->getMatrix();
    //print_x($matrix);

    $smarty->assign('filter', $filter);
    $smarty->assign('ref_pt', $pt->getActive());
    $smarty->assign('ref_pc', $ref_pc);
    $smarty->assign('ref_st', $uv_st->getActive());
    $smarty->assign('matrix', $matrix);
    $smarty->assign('list', $list);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/pointadding.list.sm.html'));
}



$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
