<?php

require_once('common.php');

$smarty->assign('title', 'Фотографии');

$ph = new MPhotos($db);

if (isset($_GET['act'])) {
    switch ($_GET['act']) {
        case 'upload':
            if (!empty($_FILES)) {
                $file = $_FILES['photo'];
                if ($file['error'] == 0) {
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
                            $imgWidth = $size[0];
                            $imgHeight = $size[1];
                            $id = $ph->insert(array(
                                'ph_title' => '',
                                'ph_author' => '',
                                'ph_link' => '',
                                'ph_src' => $fileSrc,
                                'ph_width' => $imgWidth,
                                'ph_height' => $imgHeight,
                                'ph_lat' => 0,
                                'ph_lon' => 0,
                                'ph_pc_id' => 0,
                                'ph_date_add' => $ph->now(),
                                'ph_order' => 20,
                            ));
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
} else {
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/photos.list.sm.html'));
    $smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
    exit();
}