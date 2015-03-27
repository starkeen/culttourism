<?php

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

$smarty->assign('title', 'Парсер');

$c = new MCandidatePoints($db);

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/parser.start.sm.html'));
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
