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
        $page = $_GET['page'] ?? 0;
        $searcher = Factory::buildImageSearcher();
        $searcher->setDocumentsOnPage(10); // больше 10 нельзя в бесплатной версии
        $searcher->setImageColorType('color');
        $searcher->setImageSize('large');
        $searcher->setImageType('photo');
        $result = $searcher->search($query, (int) $page);
        $out['data'] = array_map(
            static function (ResultItem $item) {
                $imageData = $item->getImageData();

                if ($imageData !== null) {
                    return [
                        'title' => $item->getTitle(),
                        'url' => $item->getUrl(),
                        'domain' => $item->getDomain(),
                        'type' => $imageData->getImageType(),
                        'height' => $imageData->getHeight(),
                        'width' => $imageData->getWidth(),
                        'size' => round($imageData->getByteSize() / 1024, 1),
                        'thumbnailUrl' => $imageData->getThumbnailLink(),
                        'thumbnailHeight' => $imageData->getThumbnailHeight(),
                        'thumbnailWidth' => $imageData->getThumbnailWidth(),
                        'context' => $imageData->getContextLink(),
                    ];
                }
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
