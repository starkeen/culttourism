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
        $urlParts = explode('/', urldecode($_GET['url']));
        $photoId = $urlParts[5];

        $data = $api->getPhotoInfo($photoId);
        $sizes = $api->getSizes($photoId);
        $out['data'] = $data;
        $out['sizes'] = $sizes;
    } elseif ($_GET['act'] == 'save') {
        $pcid = (int) $_POST['pcid'];
        
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
        
        if ($pcid > 0) {
            $pc->updateByPk($pcid, array(
                'pc_coverphoto_id' => $id,
                'pc_lastup_date' => $pc->now(),
            ));
        }

        $out['state'] = $id > 0;
    }

    header('Content-Type: application/json');
    echo json_encode($out);
    exit();
} else {
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/flickr.import.sm.html'));
    $smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
    exit();
}

