<?php

include('common.php');
include(_DIR_INCLUDES . '/class.Pager.php');

$smarty->assign('title', 'Парсер');

$c = new MCandidatePoints($db);

if (isset($_GET['act'])) {
    $out = ['state' => false, 'act' => $_GET['act'], 'data' => null, 'error' => []];
    switch ($_GET['act']) {
        case 'load_list':
            $p = new Parser($db, $_GET['url']);
            $out['data'] = $p->getList();
            $out['state'] = !empty($out['data']);
            break;
        case 'load_item':
            $p = new Parser($db, $_GET['url']);
            $out['data'] = $p->getItem();
            $out['state'] = true;
            if (!empty($_GET['mode']) && $_GET['mode'] === 'auto') {
                $cp = new MCandidatePoints($db);
                $out['state'] = $cp->add(
                        [
                            'cp_title' => $out['data']['title'],
                            'cp_text' => $out['data']['text'],
                            'cp_addr' => $out['data']['addr'],
                            'cp_phone' => $out['data']['phone'],
                            'cp_web' => $out['data']['web'],
                            'cp_worktime' => $out['data']['worktime'],
                            'cp_email' => $out['data']['email'],
                            'cp_city' => isset($_GET['city']) ? $_GET['city'] : '',
                            'cp_type_id' => 0,
                            'cp_citypage_id' => isset($_GET['pcid']) ? (int) $_GET['pcid'] : 0,
                            'cp_latitude' => $out['data']['geo_lat'],
                            'cp_longitude' => $out['data']['geo_lon'],
                            'cp_zoom' => $out['data']['geo_zoom'],
                            'cp_source_id' => 26,
                            'cp_referer' => $_GET['url'],
                        ]
                    ) > 0;
            }
            break;
    }
    header("Content-type: application/json");
    echo json_encode($out);
    exit();
}

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/parser.start.sm.html'));
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
