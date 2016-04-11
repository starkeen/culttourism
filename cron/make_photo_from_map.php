<?php

$dirSave = _DIR_DATA . '/photos';
$dirRelative = '/data/photos';
$googleAPIkey = 'AIzaSyBiuHllm_OCLEKww8y02DJPeePMtvEnTiE';
$size = 500;
$ph = new MPhotos($db);

$cities = $ph->getCityPagesWithoutPhotos();
foreach ($cities as $pc) {
    $url = sprintf("https://maps.googleapis.com/maps/api/staticmap?center=%F,%F&zoom=%d&size=%dx%d&maptype=roadmap&key=%s", $pc['pc_latitude'], $pc['pc_longitude'], $pc['pc_latlon_zoom'], $size, $size, $googleAPIkey);

    $fileName = sprintf('map_%dx%d_', $size, $size)
            . translit(str_replace(' ', '_', preg_replace("/[^a-zA-ZА-Яа-я0-9ё\s]/ui", '', mb_strtolower($pc['pc_title_unique']))))
            . '.png';

    copy($url, $dirSave . '/' . $fileName);
}