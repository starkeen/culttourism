<?php

require_once('common.php');

$smarty->assign('title', 'Flickr');

$sp = new MSysProperties($db);
$ph = new MPhotos($db);
$pc = new MPageCities($db);
$apikey = $sp->getByName('app_flikr_key');
$password = $sp->getByName('app_flikr_secret');
$api = new FlickrAPI($apikey);

if (isset($_GET['act'])) {
    $out = array();
    if ($_GET['act'] == 'fetch') {
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
    } elseif ($_GET['act'] == 'save') {
        $pcid = isset($_POST['pcid']) ? (int) $_POST['pcid'] : 0;
        $bindpc = isset($_POST['bindpc']) ? (int) $_POST['bindpc'] : 0;
        try {
            $data = $api->getPhotoInfo($_POST['phid']);
            $sizes = $api->getSizes($_POST['phid']);
            $size = !empty($sizes['sizes']['size'][7]) ? $sizes['sizes']['size'][7] : $sizes['sizes']['size'][6];
            $location = isset($data['photo']['location']) ? $data['photo']['location'] : array('latitude' => 0, 'longitude' => 0);

            $id = $ph->insert(array(
                'ph_title' => $data['photo']['title']['_content'],
                'ph_author' => $data['photo']['owner']['realname'],
                'ph_link' => $data['photo']['urls']['url'][0]['_content'],
                'ph_src' => $size['source'],
                'ph_width' => $size['width'],
                'ph_height' => $size['height'],
                'ph_lat' => $location['latitude'],
                'ph_lon' => $location['longitude'],
                'ph_pc_id' => $pcid,
                'ph_date_add' => $ph->now(),
                'ph_order' => 10,
            ));

            if ($id > 0 && $pcid > 0 && $bindpc > 0) {
                $pc->updateByPk($pcid, array(
                    'pc_coverphoto_id' => $id,
                    'pc_lastup_date' => $pc->now(),
                ));
                $pc->updateStatPhotos();
            }

            $out['state'] = $id > 0;
        } catch (Exception $e) {
            $out['state'] = false;
        }
    } elseif ($_GET['act'] == 'suggestions') {
        $out['data'] = $ph->getPopularCitiesWithOnePhoto();
    }

    header('Content-Type: application/json');
    echo json_encode($out);
    exit();
} else {
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/flickr.import.sm.html'));
    $smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
    exit();
}

