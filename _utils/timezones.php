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

$db->sql = "SELECT cd.cd_value, c.pc_title, c.pc_region_id, r.name
            FROM $dbcd cd
                LEFT JOIN $dbc c ON c.pc_id = cd.cd_pc_id
                    LEFT JOIN $dbrr r ON r.id = c.pc_region_id
            WHERE cd_cf_id = 8
                AND pc_country_id = 3159
            GROUP BY c.pc_region_id, cd.cd_value
            ORDER BY c.pc_region_id, cd.cd_value";
$db->exec();
$rows = $db->fetchAll();

print_x($rows);
?>