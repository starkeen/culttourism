<?php

$timestamp_cut = strtotime("-1 months");
$files = array();

$mask = array(
    _DIR_ROOT . '/css/ct-api-*.min.css',
    _DIR_ROOT . '/css/ct-common-*.min.css',
    _DIR_ROOT . '/js/ct-api-*.min.js',
    _DIR_ROOT . '/js/ct-city-*.min.js',
    _DIR_ROOT . '/js/ct-common-*.min.js',
    _DIR_ROOT . '/js/ct-editor-*.min.js',
    _DIR_ROOT . '/js/ct-list-*.min.js',
    _DIR_ROOT . '/js/ct-map-*.min.js',
    _DIR_ROOT . '/js/ct-point-*.min.js',
);

foreach ($mask as $id => $variant) {
    foreach (glob($variant) as $filename) {
        $timestamp = filemtime($filename);
        $files[$id][$timestamp] = array(
            'filename' => $filename,
            'timestamp' => $timestamp,
            'delete' => $timestamp < $timestamp_cut,
        );
    }
    ksort($files[$id]);
}

foreach ($files as $id => $variant) {
    $last = array_pop($variant);
    foreach ($variant as $file) {
        if ($file['delete']) {
            echo "delete old file: {$file['filename']} => " . date('d.m.Y', $file['timestamp']) . PHP_EOL;
            //unlink($file['filename']);
        }
    }
}