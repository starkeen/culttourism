<?php

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
                    $fileName = md5_file($file['tmp_name']);
                    $fileExt = Helper::getExt($file['type']);
                    $fileName .= '.' . $fileExt;
                    $fileDir1 = '/photos/' . substr($fileName, 0, 1);
                    if (!file_exists(_DIR_DATA . $fileDir1)) {
                        mkdir(_DIR_DATA . $fileDir1);
                    }
                    $fileDir2 = $fileDir1 . '/' . substr($fileName, 1, 1);
                    if (!file_exists(_DIR_DATA . $fileDir2)) {
                        mkdir(_DIR_DATA . $fileDir2);
                    }
                    $fileSrc = '/data' . $fileDir2 . '/' . $fileName;
                    $filePath = _DIR_DATA . $fileDir2 . '/' . $fileName;

                    try {
                        if (move_uploaded_file($file['tmp_name'], $filePath)) {
                            $size = getimagesize($filePath);
                            $weight = filesize($filePath);
                            $mime = mime_content_type($filePath);
                            [$imgWidth, $imgHeight] = $size;
                            $pcid = (int) $_POST['pcid'];
                            $ptid = (int) $_POST['ptid'];
                            $id = $ph->insert(
                                [
                                    'ph_title' => $_POST['title'] ?? null,
                                    'ph_author' => $_POST['author'] ?? null,
                                    'ph_link' => $_POST['link'] ?? null,
                                    'ph_src' => $fileSrc,
                                    'ph_weight' => $weight,
                                    'ph_width' => $imgWidth,
                                    'ph_height' => $imgHeight,
                                    'ph_mime' => $mime,
                                    'ph_lat' => null,
                                    'ph_lon' => null,
                                    'ph_pc_id' => $pcid ?: null,
                                    'ph_pt_id' => $ptid ?: null,
                                    'ph_date_add' => $ph->now(),
                                    'ph_order' => 20,
                                ]
                            );

                            if ($id > 0) {
                                $addpc = (int) $_POST['pcid_add'];
                                $addpt = (int) $_POST['ptid_add'];

                                if ($pcid > 0 && $addpc === 1) {
                                    $pc->updateByPk(
                                        $pcid,
                                        [
                                            'pc_coverphoto_id' => $id,
                                            'pc_lastup_date' => $pc->now(),
                                        ]
                                    );
                                }
                                if ($ptid > 0 && $addpt === 1) {
                                    $pt->updateByPk(
                                        $ptid,
                                        [
                                            'pt_photo_id' => $id,
                                            'pt_lastup_date' => $pt->now(),
                                        ]
                                    );
                                }
                            }
                        }
                    } catch (Exception $e) {
                        //
                    }
                }
            }
            break;
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
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/photos.item.sm.html'));
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
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/photos.list.sm.html'));
}

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
