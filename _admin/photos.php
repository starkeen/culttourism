<?php

use app\services\image_storage\ImageStorageFactory;

require_once('common.php');

$smarty->assign('title', 'Фотографии');

$ph = new MPhotos($db);
$pc = new MPageCities($db);
$pt = new MPagePoints($db);

if (isset($_GET['act'])) {
    switch ($_GET['act']) {
        case 'upload':
            if (!empty($_FILES)) {
                $file = $_FILES['photo'];
                if ((int) $file['error'] === 0) {
                    $imageService = ImageStorageFactory::build();
                    $photoId = $imageService->uploadFromFile($file['tmp_name']);
                    if ($photoId > 0) {
                        $pcId = (int) $_POST['pcid'];
                        $ptId = (int) $_POST['ptid'];
                        $addPc = (int) $_POST['pcid_add'];
                        $addPt = (int) $_POST['ptid_add'];

                        if ($pcId > 0 && $addPc === 1) {
                            $imageService->bindPhotoToRegion($photoId, $pcId);
                            $pc->updateByPk(
                                $pcId,
                                [
                                    'pc_coverphoto_id' => $photoId,
                                    'pc_lastup_date' => $pc->now(),
                                ]
                            );
                        }
                        if ($ptId > 0 && $addPt === 1) {
                            $imageService->bindPhotoToObject($photoId, $ptId);
                            $pt->updateByPk(
                                $ptId,
                                [
                                    'pt_photo_id' => $photoId,
                                    'pt_lastup_date' => $pt->now(),
                                ]
                            );
                        }
                    }
                }
            }
            break;
        default:
            throw new InvalidArgumentException('Ошибка роутинга');
    }

    header('Location: photos.php');
    exit;
} elseif (!empty($_GET['id'])) {
    $id = (int) $_GET['id'];
    $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'photos.php';

    if (isset($_POST) && !empty($_POST)) {
        $pcid = (int) $_POST['region_id'];
        $ptid = (int) $_POST['object_id'];
        $ph->updateByPk(
            $id,
            [
                'ph_pc_id' => $pcid,
                'ph_pt_id' => $ptid,
            ]
        );
        if (!empty($_POST['bind_region'])) {
            $pc->updateByPk(
                $pcid,
                [
                    'pc_coverphoto_id' => $id,
                    'pc_lastup_date' => $pc->now(),
                ]
            );
        }
        if (!empty($_POST['bind_object'])) {
            $pt->updateByPk(
                $ptid,
                [
                    'pt_photo_id' => $id,
                    'pt_lastup_date' => $pt->now(),
                ]
            );
        }
        if (!empty($_POST['referer'])) {
            $referer = $_POST['referer'];
        }

        header('Location: ' . $referer);
        exit;
    }

    $photo = $ph->getItemByPk($id);
    $photo['binds'] = [
        'pc' => null,
        'pt' => null,
    ];
    if ($photo['ph_pc_id']) {
        $region = $pc->getItemByPk($photo['ph_pc_id']);
        $photo['binds']['pc'] = $region['pc_title'];
    }
    if ($photo['ph_pt_id']) {
        $object = $pt->getItemByPk($photo['ph_pt_id']);
        $photo['binds']['pt'] = $object['pt_name'];
    }

    $smarty->assign('photo', $photo);
    $smarty->assign('referer', $referer);
    $smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/photos.item.tpl'));
} else {
    $get = [
        'fid' => isset($_GET['fid']) ? trim($_GET['fid']) : null,
        'ftitle' => isset($_GET['ftitle']) ? trim($_GET['ftitle']) : null,
        'fregion' => isset($_GET['fregion']) ? trim($_GET['fregion']) : null,
        'fobject' => isset($_GET['fobject']) ? trim($_GET['fobject']) : null,
        'fregionid' => isset($_GET['fregionid']) ? trim($_GET['fregionid']) : null,
        'fobjectid' => isset($_GET['fobjectid']) ? trim($_GET['fobjectid']) : null,
        'fauthor' => isset($_GET['fauthor']) ? trim($_GET['fauthor']) : null,
        'flink' => isset($_GET['flink']) ? trim($_GET['flink']) : null,
    ];
    $filter = [];
    if (!empty($get['fid'])) {
        $filter['where'][] = 'ph_id = :id';
        $filter['binds'][':id'] = (int) $get['fid'];
    }
    if (!empty($get['ftitle'])) {
        $filter['where'][] = 'ph_title LIKE :title';
        $filter['binds'][':title'] = '%' . $get['ftitle'] . '%';
    }
    if (!empty($get['fregion'])) {
        $filter['where'][] = 'pc.pc_title LIKE :region';
        $filter['binds'][':region'] = '%' . $get['fregion'] . '%';
    }
    if (!empty($get['fobject'])) {
        $filter['where'][] = 'pt.pt_name LIKE :object';
        $filter['binds'][':object'] = '%' . $get['fobject'] . '%';
    }
    if (!empty($get['fauthor'])) {
        $filter['where'][] = 'ph_author LIKE :author';
        $filter['binds'][':author'] = '%' . $get['fauthor'] . '%';
    }
    if (!empty($get['flink'])) {
        $filter['where'][] = 'ph_link LIKE :link';
        $filter['binds'][':link'] = '%' . $get['flink'] . '%';
    }
    if (!empty($get['fregionid'])) {
        $filter['where'][] = 'pc.pc_id = :pcid';
        $filter['binds'][':pcid'] = (int) $get['fregionid'];
    }
    if (!empty($get['fobjectid'])) {
        $filter['where'][] = 'pt.pt_id = :ptid';
        $filter['binds'][':ptid'] = (int) $get['fobjectid'];
    }

    $list = $ph->getItemsByFilter($filter);
    $smarty->assign('get', $get);
    $smarty->assign('list', $list);
    $smarty->assign('pager', $ph->getPager());
    $smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/photos.list.tpl'));
}

$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
