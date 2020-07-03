<?php

declare(strict_types=1);

use app\api\google_search\Factory;
use app\api\google_search\ResultItem;

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
    case 'search':
        $query = $_GET['q'] ?? '';
        $searcher = Factory::buildImageSearcher();
        $searcher->setDocumentsOnPage(10);
        $searcher->setImageColorType('color');
        $searcher->setImageSize('large');
        $searcher->setImageType('photo');
        $result = $searcher->search($query);
        $out['data'] = array_map(
            static function (ResultItem $item) {
                return [
                    'title' => $item->getTitle(),
                    'url' => $item->getUrl(),
                ];
            },
            $result->getItems()
        );
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
