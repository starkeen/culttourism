<?php

$dirSave = _DIR_DATA . '/photos';
$dirRelative = '/data/photos';
$googleAPIkey = 'AIzaSyBiuHllm_OCLEKww8y02DJPeePMtvEnTiE';
$size = 500;
$ph = new MPhotos($db);
$city = new MPageCities($db);

$cities = $ph->getCityPagesWithoutPhotos();
foreach ($cities as $pc) {
    $url = sprintf("https://maps.googleapis.com/maps/api/staticmap?center=%F,%F&zoom=%d&size=%dx%d&maptype=roadmap&key=%s", $pc['pc_latitude'], $pc['pc_longitude'], $pc['pc_latlon_zoom'], $size, $size, $googleAPIkey);

    $fileName = sprintf('map_%dx%d_', $size, $size)
            . translit(str_replace(' ', '_', preg_replace("/[^a-zA-ZА-Яа-я0-9ё\s]/ui", '', mb_strtolower($pc['pc_title_unique']))))
            . '.png';

    if (copy($url, $dirSave . '/' . $fileName)) {
        $id = $ph->insert(array(
            'ph_src' => $dirRelative . '/' . $fileName,
            'ph_title' => $pc['pc_title_unique'],
            'ph_author' => 'Google Maps',
            'ph_width' => $size,
            'ph_height' => $size,
            'ph_lat' => $pc['pc_latitude'],
            'ph_lon' => $pc['pc_longitude'],
            'ph_date_add' => $ph->now(),
        ));

        if ($id > 0) {
            $city->updateByPk($pc['pc_id'], array(
                'pc_coverphoto_id' => $id,
            ));
        } else {
            echo 'Нулевой идентификатор: ', $pc['pc_title_unique'];
        }
    } else {
        echo 'Ошибка загрузки карты: ', $pc['pc_title_unique'];
    }
}