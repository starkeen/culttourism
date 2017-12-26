<?php

$limitCitiesPerTime = 10;
$limitSitesPerAnswer = 90;

$ws = new MWordstat($db);

$currentHour = date('H');
if ($currentHour > 7 && $currentHour < 20) {
    //$limitCitiesPerTime = 5;
}

$cities = $ws->getPortionPosition($limitCitiesPerTime);

$ys = new YandexSearcher();
$ys->setPagesMax($limitSitesPerAnswer);

foreach ($cities as $city) {
    $domains = [0 => null];
    $query = sprintf('%s  достопримечательности', $city['ws_city_title']);
    $res = $ys->search($query);
    if (!$res['error_text']) {
        foreach ((array) $res['results'] as $site) {
            $domains[] = (string) $site['domain'];
        }
        $founded = array_search(_URL_ROOT, $domains, true);
        if ($founded === false) {
            $position = 0;
        } else {
            $position = $founded;
        }

        $ws->updateByPk(
            $city['ws_id'],
            [
                'ws_position' => $position,
                'ws_position_date' => $ws->now(),
            ]
        );
    } elseif ($res['error_code'] !== 15) {
        $msg = sprintf('ERROR %d: %s, query: [%s]', $res['error_code'] ?? -1, $res['error_text'], $query);
        echo $msg . PHP_EOL;
    }

    usleep(500000);
}
