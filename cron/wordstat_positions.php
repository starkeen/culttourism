<?php

$dbpc = $db->getTableName('pagecity');
$dbws = $db->getTableName('wordstat');

$limit_cities_per_time = 15;
$limit_sites_per_answer = 90;

$db->sql = "SELECT ws_id, ws_city_title
            FROM $dbws ws
                LEFT JOIN $dbpc pc ON pc.pc_city_id = ws.ws_city_id
            WHERE pc.pc_id IS NOT NULL
            ORDER BY ws_position_date, pc_rank DESC
            LIMIT $limit_cities_per_time";
$db->exec();
$cities = array();
while ($row = $db->fetch()) {
    $cities[] = $row;
}

$ys = new YandexSearcher();
$ys->setPagesMax($limit_sites_per_answer);

foreach ($cities as $city) {
    if (date("H") > 7 && date("H") < 20) {
        continue;
    }

    $domains = array(0 => null);
    $res = $ys->search("{$city['ws_city_title']} достопримечательности");
    if (!$res['error_text']) {
        foreach ($res['results'] as $site) {
            $domains[] = (string) $site['domain'];
        }
        $founded = array_search(_URL_ROOT, $domains);
        if ($founded === false) {
            $position = 0;
        } else {
            $position = $founded;
        }

        $db->sql = "UPDATE $dbws SET ws_position = '$position', ws_position_date = now() WHERE ws_id = '{$city['ws_id']}'";
        $db->exec();
    } else {
        echo "Ошибка: " . $res['error_text'];
    }
}
