<?php

declare(strict_types=1);

require_once('common.php');

$smarty->assign('title', 'Импорт фотографий');

$act = $_GET['act'] ?? null;
$out = [];
switch ($act) {
    case 'suggest':
        $ph = new MPhotos($db);
        $out['data'] = $ph->getPopularObjectsWithoutPhoto(30);
        json($out);
        break;
}

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/photos_import.list.tpl'));
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');

function json(array $data): void
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
