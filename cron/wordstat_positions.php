<?php

$dbpc = $db->getTableName('pagecity');
$dbws = $db->getTableName('wordstat');

$limit_cities_per_time = 10;
$limit_sites_per_answer = 90;

$ws = new MWordstat($db);

if (date("H") > 7 && date("H") < 20) {
    //$limit_cities_per_time = 5;
}

$cities = $ws->getPortionPosition($limit_cities_per_time);

$ys = new YandexSearcher();
$ys->setPagesMax($limit_sites_per_answer);

foreach ($cities as $city) {

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

        $ws->updateByPk($city['ws_id'], array(
            'ws_position' => $position,
            'ws_position_date' => $ws->now(),
        ));
    } else {
        echo "Ошибка: " . $res['error_text'];
    }
}
