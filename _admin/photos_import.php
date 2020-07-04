<?php

declare(strict_types=1);

use app\api\google_search\Factory;
use app\api\google_search\ResultItem;
use app\services\image_storage\ImageStorageFactory;

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
        $searcher->setImageSize('xlarge');
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
                        'type' => $imageData->getImageType() ?: 'none',
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
    case 'upload':
        $out['point_id'] = (int) ($_POST['point_id'] ?? 0);
        $out['image_url'] = $_POST['url'] ?? null;
        $out['image_page'] = $_POST['link'] ?? null;
        $service = ImageStorageFactory::build();
        $out['photo_id'] = $service->uploadFromUrl($out['image_url'], $out['image_page']);
        $service->bindPhotoToObject($out['photo_id'], $out['point_id']);
        $pt = $pt = new MPagePoints($db);
        $pt->updateByPk(
            $out['point_id'],
            [
                'pt_photo_id' => $out['photo_id'],
                'pt_lastup_date' => $pt->now(),
            ]
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
