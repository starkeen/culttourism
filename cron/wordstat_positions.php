<?php

use app\api\yandex_search\Factory;

$limitDomainsPerAnswer = 90;

$ws = new MWordstat($db);

$cities = $ws->getPortionPosition(1);

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

        $founded = array_search(GLOBAL_URL_ROOT, $domains, true);
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
}
