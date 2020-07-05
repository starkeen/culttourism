<?php

use app\api\yandex_search\Factory;

$limitCitiesPerTime = 3;
$limitDomainsPerAnswer = 90;

$ws = new MWordstat($db);

$currentHour = date('H');
if ($currentHour > 7 && $currentHour < 20) {
    //$limitCitiesPerTime = 5;
}

$cities = $ws->getPortionPosition($limitCitiesPerTime);

$searcher = Factory::build();
$searcher->setDocumentsOnPage($limitDomainsPerAnswer);

foreach ($cities as $city) {
    $domains = [
        0 => null,
    ];
    $query = sprintf('%s  достопримечательности', $city['ws_city_title']);
    $result = $searcher->searchPages($query);
    if (!$result->isError()) {
        foreach ($result->getItems() as $site) {
            $domains[] = $site->getDomain();
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
    } elseif ($result->getErrorCode() !== 15) {
        $logger->warning(
            'Ошибка в скрипте wordstat',
            [
                'query' => $query,
                'limit' => $searcher->getCurrentLimit(),
                'error_code' => $result->getErrorCode(),
                'error_text' => $result->getErrorText(),
            ]
        );
    }

    usleep(500000);
}
