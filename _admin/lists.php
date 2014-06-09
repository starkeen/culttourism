<?php

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

$smarty->assign('title', 'Списки объектов');

$lst = new Lists($db);

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $list = $lst->getItemByPk($id);

    $lstitems = new ListsItems($db, $id);

    if (isset($_POST) && !empty($_POST)) {
        if (isset($_GET['act']) && $_GET['act'] = 'add' && isset($_POST['add_id']) && intval($_POST['add_id']) > 0) {
            $res = $lstitems->insert(array('li_ls_id' => $id, 'li_pt_id' => intval($_POST['add_id'])));
            if ($res) {
                $lst->updateByPk($id, array('ls_update_date' => date('Y-m-D H:i:s')));
                header("Location: lists.php?id=$id");
                exit();
            }
        } else {
            $upds = $_POST;
            $upds['ls_update_date'] = date('Y-m-d H:i:s');
            $res = $lst->updateByPk($id, $upds);
            if ($res) {
                header("Location: lists.php");
                exit();
            }
        }
    }

    $smarty->assign('list', $list);
    $smarty->assign('list_items', $lstitems->getAll());
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/lists.item.sm.html'));
} elseif (isset($_GET['suggest'])) {
    $out = array('query' => '', 'suggestions' => array());
    $out['query'] = htmlentities(cut_trash_string($_GET['query']), ENT_QUOTES, "UTF-8");
    $lid = intval($_GET['lid']);

    $lstitems = new ListsItems($db, $lid);
    $variants = $lstitems->getSuggestion($out['query']);
    foreach ($variants as $variant) {
        $out['suggestions'][] = array(
            'value' => "{$variant['pt_name']}",
            'oid' => "{$variant['pt_id']}",
        );
    }
    if (empty($out['suggestions'])) {
        $out['suggestions'][] = array(
            'value' => "-- не найдено --",
            'oid' => "",
        );
    }

    header('Content-type: application/json');
    echo json_encode($out);
    exit();
} else {
    $smarty->assign('lists', $lst->getAll());
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/lists.list.sm.html'));
}

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
