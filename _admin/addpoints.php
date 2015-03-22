<?php

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

$smarty->assign('title', 'Заявки на добавление');

$c = new MCandidatePoints($db);

if (isset($_GET['id'])) {
    $item = $c->getItemByPk(intval($_GET['id']));
    $smarty->assign('claim', $item);
    // -----------   обработка заявки ----------
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/pointadding.item.sm.html'));
} else {
    // -----------   список активных ----------
    $smarty->assign('list', $c->getActive());
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/pointadding.list.sm.html'));
}



$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
