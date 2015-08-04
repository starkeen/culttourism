<?php

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
$dbp = $db->getTableName('pagepoints');
$dbcd = $db->getTableName('city_data');
$dbrr = $db->getTableName('ref_region');

$db->sql = "SELECT pt.pt_id, pt.pt_name, pt.pt_adress,
                    pt.pt_latitude, pt.pt_longitude,
                    pc.pc_title, pc.pc_latitude, pc.pc_longitude
                
        FROM $dbp pt
            LEFT JOIN $dbc pc ON pc.pc_id = pt.pt_citypage_id
        WHERE pt.pt_active = 1
            AND ABS(CHAR_LENGTH(pt.pt_adress)-CHAR_LENGTH(pc.pc_title)) < 6
            AND pt.pt_latitude IS NOT NULL
        ORDER BY RAND()
        LIMIT 50";
//$db->showSQL();
$db->exec();
$points = array();
while ($row = $db->fetch()) {
    $row['meta_founded'] = 0;
    $row['addr_variant'] = array();
    $points[] = $row;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

foreach ($points as $i => $pt) {
    $request = 'https://geocode-maps.yandex.ru/1.x/?format=json'
            . '&geocode=' . $pt['pt_longitude'] . ',' . $pt['pt_latitude']
            . '&ll=37.618920,55.756994'
            . '&kind=house&results=1';


    curl_setopt($ch, CURLOPT_URL, $request);
    $answer = curl_exec($ch);
    if (!$answer) {
        echo (curl_error($ch));
    }

    $data = json_decode($answer);
    //print_x($data);
    $points[$i]['meta_founded'] = $data->response->GeoObjectCollection->metaDataProperty->GeocoderResponseMetaData->found > 0;
    $featureMember = $data->response->GeoObjectCollection->featureMember;
    foreach ($featureMember as $fm) {
        $latlon = explode(' ', $fm->GeoObject->Point->pos);
        $points[$i]['addr_variant'] = array(
            'text' => $fm->GeoObject->metaDataProperty->GeocoderMetaData->text,
            'gps' => array(
                'latitude' => $latlon[1],
                'longitude' => $latlon[0],
            ),
            'delta_lat' => round(abs($pt['pt_latitude'] - $latlon[1]), 5),
            'delta_lon' => round(abs($pt['pt_longitude'] - $latlon[0]), 5),
            'delta_meters' => Helper::distanceGPS($pt['pt_latitude'], $pt['pt_longitude'], $latlon[1], $latlon[0]),
        );
    }
}
curl_close($ch);

$log = array();
foreach ($points as $point) {
    if ($point['meta_founded'] && $point['addr_variant']['delta_meters'] < 10) {
        $db->sql = "UPDATE $dbp SET pt_adress = '{$point['addr_variant']['text']}'
                WHERE pt_id = '{$point['pt_id']}'";
        $db->exec();
        $log[] = array(
            $point['pt_id'],
            $point['pt_name'],
            $point['pt_adress'],
            $point['addr_variant']['text'],
            round($point['addr_variant']['delta_meters'], 2),
        );
    }
}

print_r($log);