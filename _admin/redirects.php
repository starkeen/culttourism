<?php

declare(strict_types=1);

use app\model\entity\Redirect;
use app\model\repository\RedirectsRepository;

include('common.php');

$act = $_GET['act'] ?? null;

if ($act === 'upload') {
    $redirect = new Redirect([]);
    $redirect->rd_from = $_POST['from'];
    $redirect->rd_to = $_POST['to'];

    $redirectRepository = new RedirectsRepository($db);
    $redirectRepository->save($redirect);

    header('Location: redirects.php');
    exit;
}

$smarty->assign('title', 'Редиректы');

$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/redirects.list.tpl'));

$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
