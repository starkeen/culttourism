<?php

$config = array(
    'css' => array(
        'common' => array(
            _DIR_ROOT . '/css/common-layout.css',
            _DIR_ROOT . '/css/common-modules.css',
            _DIR_ROOT . '/addons/autocomplete/autocomplete.css',
            _DIR_ROOT . '/css/common-print.css',
            _DIR_ROOT . '/addons/simplemodal/simplemodal.css',
            _DIR_ROOT . '/css/common-media-queries.css',
        ),
        'api' => array(
            _DIR_ROOT . '/css/api.css',
        ),
    ),
    'js' => array(
        'common' => array(
            _DIR_ROOT . '/addons/simplemodal/jquery.simplemodal.1.4.4.min.js',
            _DIR_ROOT . '/addons/autocomplete/jquery.autocomplete.min.js',
            _DIR_ROOT . '/js/main.js',
        ),
        'map' => array(
            _DIR_ROOT . '/addons/simplemodal/jquery.simplemodal.1.4.4.min.js',
            _DIR_ROOT . '/addons/autocomplete/jquery.autocomplete.min.js',
            _DIR_ROOT . '/js/main.js',
            _DIR_ROOT . '/js/map.js',
        ),
        'list' => array(
            _DIR_ROOT . '/addons/simplemodal/jquery.simplemodal.1.4.4.min.js',
            _DIR_ROOT . '/addons/autocomplete/jquery.autocomplete.min.js',
            _DIR_ROOT . '/js/main.js',
            _DIR_ROOT . '/js/map_page_list.js',
        ),
        'city' => array(
            _DIR_ROOT . '/addons/simplemodal/jquery.simplemodal.1.4.4.min.js',
            _DIR_ROOT . '/addons/autocomplete/jquery.autocomplete.min.js',
            _DIR_ROOT . '/js/main.js',
            _DIR_ROOT . '/js/map_page_city.js',
            _DIR_ROOT . '/js/adv_city.js',
        ),
        'point' => array(
            _DIR_ROOT . '/addons/simplemodal/jquery.simplemodal.1.4.4.min.js',
            _DIR_ROOT . '/addons/autocomplete/jquery.autocomplete.min.js',
            _DIR_ROOT . '/js/main.js',
            _DIR_ROOT . '/js/adv_point.js',
            _DIR_ROOT . '/js/map_page_point.js',
            _DIR_ROOT . '/js/panoramio_page_point.js',
        ),
        'api' => array(
            _DIR_ROOT . '/js/api.js',
        ),
        'editor' => array(
            _DIR_ROOT . '/js/jquery.ui.core.js',
            _DIR_ROOT . '/js/jquery.ui.datepicker.js',
            _DIR_ROOT . '/js/jquery.ui.datepicker-ru.js',
        ),
    ),
);

//-----------   C S S   ----------------------
foreach ($config['css'] as $pack => $files) {
    $file_out = _DIR_ROOT . '/css/ct-' . $pack . '.css';
    file_put_contents($file_out, '');
    foreach ($files as $file) {
        file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
        file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
    }
    $file_hash_new = crc32(file_get_contents($file_out));
    $file_production = _DIR_ROOT . '/css/ct-' . $pack . '-' . $file_hash_new . '.min.css';
    if (!file_exists($file_production)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://cssminifier.com/raw');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('input' => trim(file_get_contents($file_out)))));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $minified = curl_exec($ch);
        curl_close($ch);
        if ($minified != '') {
            file_put_contents($file_production, $minified);
        }
    }
    unlink($file_out);
}

//------------   J S   -----------------------
foreach ($config['js'] as $pack => $files) {
    $file_out = _DIR_ROOT . '/js/ct-' . $pack . '.js';
    file_put_contents($file_out, '');
    foreach ($files as $file) {
        file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
        file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
    }
    $file_hash_new = crc32(file_get_contents($file_out));
    $file_production = _DIR_ROOT . '/js/ct-' . $pack . '-' . $file_hash_new . '.min.js';
    if (!file_exists($file_production)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://javascript-minifier.com/raw');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('input' => trim(file_get_contents($file_out)))));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $minified = curl_exec($ch);
        curl_close($ch);
        if ($minified != '') {
            file_put_contents($file_production, $minified);
            //unlink(_DIR_ROOT . '/js/ct-' . $pack . '-' . $file_hash_old . '.min.js');
        }
    }
    unlink($file_out);
}