<?php

/* Админка */

require_once '_common.php';

$dadata = $app->getDadata();

$smarty->assign('title', 'Административная часть сайта');
$smarty->assign('content', 'Выберите раздел в меню выше');

$smarty->assign('yandexSearchLimit', '');
$smarty->assign('dadataLimit', $dadata->getBalance());

$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/index.tpl'));

$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');

exit();
