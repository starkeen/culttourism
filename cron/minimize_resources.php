<?php

$config = array(
    'css' => array(
        'common' => array(
            _DIR_ROOT . '/css/ui-lightness/jquery-ui-1.8.2.custom.css',
            _DIR_ROOT . '/css/common-layout.css',
            _DIR_ROOT . '/css/common-modules.css',
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
        //
        ),
        'city' => array(
        //
        ),
        'point' => array(
        //
        ),
    ),
);

//-----------   C S S   ----------------------
foreach ($config['css'] as $pack => $files) {
    $file_out = _DIR_ROOT . '/css/ct-' . $pack . '.css';
    $file_hash_old = crc32(file_get_contents($file_out));
    file_put_contents($file_out, '');
    foreach ($files as $file) {
        file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
        file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
    }
    $file_hash_new = crc32(file_get_contents($file_out));
    if ($file_hash_new != $file_hash_old) {
        $file_production = _DIR_ROOT . '/css/ct-' . $pack . '-' . $file_hash_new . '.css';
        $file_production_min = _DIR_ROOT . '/css/ct-' . $pack . '-' . $file_hash_new . '.min.css';
        copy($file_out, $file_production);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://cssminifier.com/raw');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('input' => file_get_contents($file_production)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $minified = curl_exec($ch);
        curl_close($ch);
        file_put_contents($file_production_min, $minified);
    }
}

//------------   J S   -----------------------
foreach ($config['js'] as $pack => $files) {
    foreach ($files as $file) {
        //
    }
}