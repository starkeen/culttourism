<?php

declare(strict_types=1);

require_once('common.php');

$smarty->assign('title', 'Импорт фотографий');

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/photos_import.list.tpl'));
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
