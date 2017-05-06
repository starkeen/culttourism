<?php

/**
 * Description of check_files_dates
 *
 * @author Андрей
 */
$dbsp = $db->getTableName('siteprorerties');

clearstatcache(true);
$files = array();
$files_skip = array();
$timestamp_max = 0;
$filename_last = '';

$scan_dirs = array(
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
);

$files[] = _DIR_ROOT . '/index.php';
$files[] = _DIR_ROOT . '/robots.txt';
$files[] = _DIR_ROOT . '/.htaccess';
$files_skip[] = _DIR_ROOT . '/data/feed/blog.xml';
$files_skip[] = _DIR_ROOT . '/data/feed/blog-dlvrit.xml';
$files_skip[] = _DIR_ROOT . '/data/feed/blog-facebook.xml';
$files_skip[] = _DIR_ROOT . '/data/feed/blog-facebook-dev.xml';
$files_skip[] = _DIR_ROOT . '/data/feed/blog-facebook-ifttt.xml';
$files_skip[] = _DIR_ROOT . '/data/feed/blog-twitter.xml';
$files_skip[] = _DIR_ROOT . '/data/feed/blog-telegram.xml';

foreach ($scan_dirs as $dir) {
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
foreach ($files_skip as $filename) {
    $idx = array_search($filename, $files);
    if ($idx > 0) {
        unset($files[$idx]);
    }
}

foreach ($files as $filename) {
    $timestamp = filemtime($filename);
    if ($timestamp > $timestamp_max) {
        $timestamp_max = $timestamp;
        $filename_last = $filename;
    }
}

$sp = new MSysProperties($db);
$lastupdate = $sp->getByName('site_lastupdate');

$sp->updateByName('site_lastupdate', $timestamp_max);
$sp->updateByName('site_version', date('Ymd-Hi', $timestamp_max));

if ($lastupdate != $timestamp_max) {
    //тревожное пимьмо
    include_once _DIR_INCLUDES . '/class.Mailing.php';
    $mail_attrs = array(
        'datetime_max' => date('d.m.Y H:i:s', $timestamp_max),
        'datetime_last' => date('d.m.Y H:i:s', $lastupdate),
        'filename_last' => $filename_last,
    );
    Mailing::sendLetterCommon($global_cron_email, 3, $mail_attrs);
}
