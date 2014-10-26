<?php

/*
 * Изменение исчисления времени произойдет 26 октября 2014 года в 2:00 местного времени.
 * http://habrahabr.ru/post/239827/
 * UTC+4 -> UTC+3
 * Магадан 12 -> 10
 * Исключения составляют:
 * в Республике Удмуртия и Самарской области следует выполнить ручное переключение на часовой пояс “Russian Time Zone 3” вместо автоматически установленного часового пояса RTZ 2 (Russian Time Zone 2);
 * в Кемеровской области следует выполнить ручное переключение на часовой пояс “Russian Time Zone 6” вместо автоматически установленного часового пояса RTZ 5 (Russian Time Zone 5);
 * в Забайкальском крае следует выполнить ручное переключение на часовой пояс “Russian Time Zone 7” вместо автоматически установленного часового пояса RTZ 8 (Russian Time Zone 8).
 */

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
include(realpath(dirname(__FILE__) . '/../config/configuration.php'));

include(_DIR_ROOT . '/includes/class.myDB.php');
include(_DIR_ROOT . '/includes/class.mySmarty.php');
include(_DIR_ROOT . '/includes/class.Logging.php');
include(_DIR_ROOT . '/includes/debug.php');

include(_DIR_INCLUDES . '/class.Helper.php');
spl_autoload_register('Helper::autoloader');

$db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);

$dbc = $db->getTableName('pagecity');
$dbcd = $db->getTableName('city_data');
$dbrr = $db->getTableName('ref_region');

$tz_new = array(
    'UTC+3' => 'UTC+2',
    'UTC+4' => 'UTC+3',
    'UTC+5' => 'UTC+4',
    'UTC+6' => 'UTC+5',
    'UTC+7' => 'UTC+6',
    'UTC+8' => 'UTC+7',
    'UTC+9' => 'UTC+8',
    'UTC+10' => 'UTC+9',
    'UTC+11' => 'UTC+10',
    'UTC+12' => 'UTC+11',
);

$db->sql = "SELECT cd.cd_value as utc
            FROM $dbcd cd
                LEFT JOIN $dbc c ON c.pc_id = cd.cd_pc_id
                    LEFT JOIN $dbrr r ON r.id = c.pc_region_id
            WHERE cd_cf_id = 8
                AND pc_country_id = 3159
            GROUP BY cd.cd_value
            ORDER BY cd.cd_value, c.pc_region_id";
$db->exec();
$timezones = $db->fetchAll();

foreach ($timezones as $tzid => $zone) {
    $db->sql = "SELECT c.pc_id, c.pc_title, c.pc_region_id
                FROM $dbc c
                    LEFT JOIN $dbcd cd ON cd.cd_pc_id = c.pc_id
                WHERE c.pc_country_id = 3159
                    AND cd.cd_value = '{$zone['utc']}'";
    $db->exec();
    $timezones[$tzid]['utc_new'] = $tz_new[$zone['utc']];
    $timezones[$tzid]['pages'] = array();
    while ($row = $db->fetch()) {
        $row['tz_old'] = $zone['utc'];
        if ($row['pc_region_id'] == 4243) {//Магадан
            $row['tz_new'] = 'UTC+10';
        } elseif ($row['pc_region_id'] == 5555) {//Забайкалье-Чита
            $row['tz_new'] = 'UTC+8';
        } elseif (in_array($row['pc_region_id'], array(3921, 3872, 2415585))) {//Кемерово, Камчатка, Чукотка
            $row['tz_new'] = $row['tz_old'];
        } else {
            $row['tz_new'] = $tz_new[$zone['utc']];
        }
        $timezones[$tzid]['pages'][] = $row;
    }
    ksort($timezones[$tzid]);
}

foreach ($timezones as $tz) {
    foreach ($tz['pages'] as $pg) {
        if ($pg['tz_new']) {
            $db->sql = "UPDATE $dbcd SET cd_value = '{$pg['tz_new']}' WHERE cd_cf_id = 8 cd_pc_id = '{$pg['pc_id']}'; #{$pg['pc_title']}";
            $db->exec();
        }
    }
}
