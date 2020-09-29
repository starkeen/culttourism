<?php

declare(strict_types=1);

use app\sys\TemplateEngine;
use models\MLinks;

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

/** @var TemplateEngine $smarty */
$smarty->assign('title', 'Списки объектов');

$linksModel = new MLinks($db);

$smarty->assign('links', $linksModel->getList(20));
$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/links.list.tpl'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
