<?php

/* Админка */

use app\api\yandex_search\Factory;

require_once('common.php');

$smarty->assign('title', 'Административная часть сайта');
$smarty->assign('content', 'Выберите раздел в меню выше');

$yandexSearcher = Factory::build();
$yandexSearchLimit = $yandexSearcher->getCurrentLimit();

$smarty->assign('yandexSearchLimit', $yandexSearchLimit);

$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES.'/_admin/index.tpl'));

$smarty->display(GLOBAL_DIR_TEMPLATES.'/_admin/admpage.tpl');

exit();
