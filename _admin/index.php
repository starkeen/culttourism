<?php

/* Админка */

require_once 'common.php';

$smarty->assign('title', 'Административная часть сайта');
$smarty->assign('content', 'Выберите раздел в меню выше');

$smarty->assign('yandexSearchLimit', '');

$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES.'/_admin/index.tpl'));

$smarty->display(GLOBAL_DIR_TEMPLATES.'/_admin/admpage.tpl');

exit();
