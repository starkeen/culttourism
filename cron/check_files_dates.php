<?php

/**
 * Description of check_files_dates
 *
 * @author Андрей
 */
$dbsp = $db->getTableName('siteprorerties');

clearstatcache(true);
$files = array();
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

foreach ($files as $filename) {
    $timestamp = filemtime($filename);
    if ($timestamp > $timestamp_max) {
        $timestamp_max = $timestamp;
        $filename_last = $filename;
    }
}

$db->sql = "SELECT sp_value FROM $dbsp WHERE sp_name = 'site_lastupdate'";
$db->exec();
$row = $db->fetch();
$db->sql = "UPDATE $dbsp SET sp_value = '$timestamp_max' WHERE sp_name = 'site_lastupdate'";
$db->exec();

$db->sql = "UPDATE $dbsp SET sp_value = FROM_UNIXTIME($timestamp_max, '%Y%m%d-%H%i') WHERE sp_name = 'site_version'";
$db->exec();

if ($row['sp_value'] != $timestamp_max) {
    //тревожное пимьмо
    include_once _DIR_INCLUDES . '/class.Mailing.php';
    $mail_attrs = array(
        'datetime_max' => date('d.m.Y H:i:s', $timestamp_max),
        'datetime_last' => date('d.m.Y H:i:s', $row['sp_value']),
        'filename_last' => $filename_last,
    );
    Mailing::sendLetterCommon($global_cron_email, 3, $mail_attrs);
}
