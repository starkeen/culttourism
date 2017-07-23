<?php

/* Админка */
require_once('common.php');

$smarty->assign('title', 'Административная часть сайта');
$smarty->assign('content', 'Выберите раздел в меню выше');

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES.'/_admin/index.sm.html'));

$smarty->display(_DIR_TEMPLATES.'/_admin/admpage.sm.html');

exit();
