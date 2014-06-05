<?php

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

$smarty->assign('title', 'Списки объектов');

$lst = new Lists($db);

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $list = $lst->getItemByPk($id);

    if (isset($_POST) && !empty($_POST)) {
        $res = $lst->updateByPk($id, $_POST);
        if ($res) {
            header("Location: lists.php");
            exit();
        }
    }

    $smarty->assign('list', $list);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/lists.item.sm.html'));
} else {
    $smarty->assign('lists', $lst->getAll());
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/lists.list.sm.html'));
}

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
