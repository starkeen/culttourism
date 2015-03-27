<?php

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

$smarty->assign('title', 'Парсер');

$c = new MCandidatePoints($db);

if (isset($_GET['act'])) {
    $out = array('state' => false, 'act' => $_GET['act'], 'data' => null, 'error' => array());
    switch ($_GET['act']) {
        case "load_list":
            $p = new Parser($db, $_GET['url']);
            $out['data'] = $p->getList();
            $out['state'] = true;
            break;
    }
    header("Content-type: text/json");
    echo json_encode($out);
    exit();
}

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/parser.start.sm.html'));
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
