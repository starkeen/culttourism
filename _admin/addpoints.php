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
    }
    $out['data'] = $c->getItemByPk($out['id']);
    header("Content-type: text/json");
    echo json_encode($out);
    exit();
} elseif (isset($_GET['id']) && !isset($_GET['act'])) {
    $rpt = new MRefPointtypes($db);

    $item = $c->getItemByPk(intval($_GET['id']));

    $smarty->assign('claim', $item);
    $smarty->assign('ref_types', $rpt->getActive());
    // -----------   обработка заявки ----------
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/pointadding.item.sm.html'));
} else {
    // -----------   список активных ----------
    $smarty->assign('list', $c->getActive());
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/pointadding.list.sm.html'));
}



$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
