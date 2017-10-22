<?php

/**
 * Проверка дат последнего изменения файлов
 */
$sp = new MSysProperties($db);

clearstatcache(true);
$files = [];
$filesSkip = [];
$timestampMax = 0;
$filenameLast = '';

$scanDirs = [
    '_admin',
    '_utils',
    'addons',
    'config',
    'cron',
    'data',
    'img',
    'includes',
    'models',
    'js',
    'css',
    'modules',
    'templates',
];
$skipDirs = [
    _DIR_ROOT . '/data/logs',
    _DIR_ROOT . '/data/feed',
];

$files[] = _DIR_ROOT . '/index.php';
$files[] = _DIR_ROOT . '/robots.txt';
$files[] = _DIR_ROOT . '/.htaccess';

foreach ($scanDirs as $dir) {
    foreach (glob(_DIR_ROOT . "/$dir/*.*") as $filename) {
        $files[] = $filename;
    }
    foreach (glob(_DIR_ROOT . "/$dir/*/*.*") as $filename) {
        $files[] = $filename;
    }
    foreach (glob(_DIR_ROOT . "/$dir/*/*/*.*") as $filename) {
        $files[] = $filename;
    }
}

foreach ($skipDirs as $dir) {
    foreach (glob("$dir/*.*") as $filename) {
        $filesSkip[] = $filename;
    }
    foreach (glob("$dir/*/*.*") as $filename) {
        $filesSkip[] = $filename;
    }
    foreach (glob("$dir/*/*/*.*") as $filename) {
        $filesSkip[] = $filename;
    }
}

foreach ($filesSkip as $filename) {
    $idx = array_search($filename, $files, true);
    if ($idx > 0) {
        unset($files[$idx]);
    }
}

foreach ($files as $filename) {
    $timestamp = filemtime($filename);
    if ($timestamp > $timestampMax) {
        $timestampMax = $timestamp;
        $filenameLast = $filename;
    }
}

$lastUpdate = (int) $sp->getByName('site_lastupdate');

$sp->updateByName('site_lastupdate', $timestampMax);
$sp->updateByName('site_version', date('Ymd-Hi', $timestampMax));

if ($lastUpdate !== $timestampMax) {
    //тревожное пимьмо
    include_once _DIR_INCLUDES . '/class.Mailing.php';
    $mailAttrs = [
        'datetime_max' => date('d.m.Y H:i:s', $timestampMax),
        'datetime_last' => date('d.m.Y H:i:s', $lastUpdate),
        'filename_last' => $filenameLast,
    ];
    Mailing::sendLetterCommon($global_cron_email, 3, $mailAttrs);
}
