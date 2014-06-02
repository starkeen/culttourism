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

foreach ($cities as $city) {
    $result = array();
    $result[0] = 'null';
    $result_meta = array();
    $found = array();
    $result_meta['pages'] = $limit_sites_per_answer;
    $result_meta['page'] = 0;
    $result_meta['page_all'] = 0;
    $doc = <<<DOC
<?xml version='1.0' encoding='utf-8'?>
<request>
    <query>{$city['ws_city_title']} достопримечательности</query>
    <maxpassages>5</maxpassages>
    <groupings>
        <groupby attr="" mode="flat" groups-on-page="{$result_meta['pages']}"  docs-in-group="1" />
    </groupings>
    <page>{$result_meta['page']}</page>
</request>
DOC;
    $context = stream_context_create(array(
        'http' => array(
            'method' => "POST",
            'header' => "Content-type: application/xml\r\n" .
            "Content-length: " . strlen($doc),
            'content' => $doc
    )));
    $response = file_get_contents('http://xmlsearch.yandex.ru/xmlsearch?user=starkeen&key=03.10766361:bbf1bd34a06a8c93a745fcca95b31b80&lr=225', true, $context);
    // $response = file_get_contents('http://xmlsearch.yandex.ru/xmlsearch?user=starkeen&key=03.10766361:bbf1bd34a06a8c93a745fcca95b31b80&lr=1', true, $context);
    if ($response) {
        $xmldoc = new SimpleXMLElement($response);
        $error = $xmldoc->response->error;
        $found = $xmldoc->xpath("response/results/grouping/group/doc");
        if ($error) {
            echo "Ошибка: " . $error[0];
        } else {
            $result_meta['pages_all'] = $xmldoc->response->found;
            foreach ($found as $item) {
                $result[] = (string) $item->domain;
            }
        }
    }
    $founded = array_search('culttourism.ru', $result);
    if ($founded === false) {
        $position = 0;
    } else {
        $position = $founded;
    }

    $db->sql = "UPDATE $dbws SET ws_position = '$position', ws_position_date = now() WHERE ws_id = '{$city['ws_id']}'";
    $db->exec();
}
?>
