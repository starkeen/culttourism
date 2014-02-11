<?php

include('common.php');

$smarty->assign('title', 'Объекты в базе');

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/points.list.sm.html'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
