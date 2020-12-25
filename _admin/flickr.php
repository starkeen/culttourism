<?php

require_once('common.php');

$smarty->assign('title', 'Flickr');

$sp = new MSysProperties($db);
$ph = new MPhotos($db);
$citiesModel = new MPageCities($db);
$pointsModel = new MPagePoints($db);
$apikey = $sp->getByName('app_flikr_key');
$password = $sp->getByName('app_flikr_secret');
$api = new FlickrAPI($apikey);

if (isset($_GET['act'])) {
    $out = [];
    if ($_GET['act'] === 'fetch') {
        $urlData = parse_url($_GET['url']);
        if ($urlData['scheme'] === 'https' && $urlData['host'] === 'www.flickr.com') {
            $urlParts = explode('/', urldecode($_GET['url']));
            $photoId = $urlParts[5];
        } elseif ($urlData['scheme'] === 'https' && $urlData['host'] === 'flic.kr') {
            $photoId = 0;
        } else {
            $photoId = 0;
        }

        $data = $api->getPhotoInfo($photoId);
        $sizes = $api->getSizes($photoId);
        $geo = $api->getLocation($photoId);
        $out['data'] = $data;
        $out['sizes'] = $sizes;
        $out['geo'] = $geo;
    } elseif ($_GET['act'] === 'save') {
        $pcid = isset($_POST['pcid']) ? (int) $_POST['pcid'] : 0;
        $ptid = isset($_POST['ptid']) ? (int) $_POST['ptid'] : 0;
        $bindpc = isset($_POST['bindpc']) ? (int) $_POST['bindpc'] : 0;
        $bindpt = isset($_POST['bindpt']) ? (int) $_POST['bindpt'] : 0;
        try {
            $data = $api->getPhotoInfo($_POST['phid']);
            $sizes = $api->getSizes($_POST['phid']);
            $size = $sizes['sizes']['size'][7] ?? $sizes['sizes']['size'][6] ?? $sizes['sizes']['size'][5];
            $location = $data['photo']['location'] ?? [
                'latitude' => 0,
                'longitude' => 0
            ];

            $id = $ph->insert(
                [
                    'ph_title' => $data['photo']['title']['_content'],
                    'ph_author' => $data['photo']['owner']['realname'],
                    'ph_link' => $data['photo']['urls']['url'][0]['_content'],
                    'ph_src' => $size['source'],
                    'ph_width' => $size['width'],
                    'ph_height' => $size['height'],
                    'ph_lat' => $location['latitude'],
                    'ph_lon' => $location['longitude'],
                    'ph_pc_id' => $pcid,
                    'ph_pt_id' => $ptid,
                    'ph_date_add' => $ph->now(),
                    'ph_order' => 10,
                ]
            );

            if ($id > 0) {
                if ($pcid > 0 && $bindpc > 0) {
                    $citiesModel->updateByPk(
                        $pcid,
                        [
                            'pc_coverphoto_id' => $id,
                            'pc_lastup_date' => $citiesModel->now(),
                        ]
                    );
                    $citiesModel->updateStatPhotos();
                }
                if ($ptid > 0 && $bindpt > 0) {
                    $pointsModel->updateByPk(
                        $ptid,
                        [
                            'pt_photo_id' => $id,
                            'pt_lastup_date' => $pointsModel->now(),
                        ]
                    );
                    $citiesModel->updateStatPhotos();
                }
            }

            $out['state'] = $id > 0;
        } catch (Exception $e) {
            $out['state'] = false;
        }
    } elseif ($_GET['act'] === 'suggestions') {
        $out['data'] = $ph->getPopularCitiesWithOnePhoto();
    } elseif ($_GET['act'] === 'object_suggestions') {
        $out['data'] = $ph->getPopularObjectsWithoutPhoto();
    }

    header('Content-Type: application/json');
    echo json_encode($out);
    exit();
} else {
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/flickr.import.tpl'));
    $smarty->display(_DIR_TEMPLATES . '/_admin/admpage.tpl');
    exit();
}
