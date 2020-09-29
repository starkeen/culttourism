<?php

declare(strict_types=1);

use app\sys\TemplateEngine;
use models\MLinks;

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

/** @var TemplateEngine $smarty */
$smarty->assign('title', 'Ссылки для ручной проверки');

$linksModel = new MLinks($db);

$urls = $linksModel->getList(20);
$pager = new Pager($urls);

$smarty->assign('links', $pager->out);
$smarty->assign('pager', $pager->pages);
$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/links.list.tpl'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
