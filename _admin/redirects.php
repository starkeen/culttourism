<?php

declare(strict_types=1);

use app\cache\Cache;
use app\db\exceptions\DuplicateKeyException;
use app\model\entity\Redirect;
use app\model\repository\RedirectsRepository;
use config\CachesConfig;

require_once '_common.php';

$act = $_GET['act'] ?? null;

if ($act === 'upload') {
    $redirect = new Redirect([]);
    $redirect->rd_from = $_POST['from'];
    $redirect->rd_to = $_POST['to'];

    $redirectRepository = new RedirectsRepository($db);

    try {
        $redirectRepository->save($redirect);

        $cache = Cache::i(CachesConfig::REDIRECTS);
        $cache->remove('active');
    } catch (DuplicateKeyException $exception) {
        // пропускаем повторное сохранение редиректа
    }

    header('Location: redirects.php');
    exit;
}

$smarty->assign('title', 'Редиректы');

$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/redirects.list.tpl'));

$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
