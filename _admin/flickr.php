<?php

require_once('common.php');

$smarty->assign('title', 'Flickr');

$sp = new MSysProperties($db);
$ph = new MPhotos($db);
$apikey = $sp->getByName('app_flikr_key');
$password = $sp->getByName('app_flikr_secret');
$api = new FlickrAPI($apikey);

if (isset($_GET['act'])) {
    $out = array();
    if ($_GET['act'] == 'fetch') {
        $urlParts = explode('/', urldecode($_GET['url']));
        $photoId = $urlParts[5];

        $data = $api->getPhotoInfo($photoId);
        $out['data'] = $data;
    } elseif ($_GET['act'] == 'save') {
        $data = $api->getPhotoInfo($_POST['phid']);

        $ph->insert(array(
            'ph_title' => $data['photo']['title']['_content'],
            'ph_author' => $data['photo']['owner']['realname'],
            'ph_link' => $data['photo']['urls']['url'][0]['_content'],
            'ph_src' => $data['photo']['urls']['url'][0]['_content'],
            'ph_width' => 0,
            'ph_height' => 0,
            'ph_lat' => $data['photo']['location']['latitude'],
            'ph_lon' => $data['photo']['location']['longitude'],
            'ph_pc_id' => (int) $_POST['pcid'],
            'ph_date_add' => $ph->now(),
            'ph_order' => 10,
        ));

        $out['state'] = true;
    }

    header('Content-Type: application/json');
    echo json_encode($out);
    exit();
} else {
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/flickr.import.sm.html'));
    $smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
    exit();
}

