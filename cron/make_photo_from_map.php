<?php

$dirSave = _DIR_DATA . '/photos';
$dirRelative = '/data/photos';
$googleAPIkey = 'AIzaSyBiuHllm_OCLEKww8y02DJPeePMtvEnTiE';
$size = 500;
$ph = new MPhotos($db);

$cities = $ph->getCityPagesWithoutPhotos();
foreach ($cities as $pc) {
    $url = sprintf("https://maps.googleapis.com/maps/api/staticmap?center=%s,%s6&zoom=%s&size=%sx%s&maptype=roadmap&key=%s", $pc['pc_latitude'], $pc['pc_longitude'], $pc['pc_latlon_zoom'], $size, $size, $googleAPIkey);
    echo $url, "\n";
}