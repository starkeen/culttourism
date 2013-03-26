<?php

$dbrc = $db->getTableName('ref_city');
$dbpc = $db->getTableName('pagecity');
$dbws = $db->getTableName('wordstat');

$db->sql = "SELECT * FROM $dbws
            WHERE ws_rep_id = 0
            LIMIT 10";
$db->exec();
$_ids = array();
$request = array(
    'method' => 'CreateNewWordstatReport',
    'param' => array(
        'Phrases' => array(),
        'GeoID' => array(213),
    ),
);
while ($row = $db->fetch()) {
    $request['param']['Phrases'][] = iconv('ISO-8859-1', 'utf-8', $row['ws_city_title'] . ' достопримечательности');
    $_ids[] = $row['ws_id'];
}
$res = yandex_req($request);
if (isset($res['data'])) {
    $res_id = intval($res['data']);
    $db->sql = "UPDATE $dbws SET ws_rep_id = '$res_id' WHERE ws_id IN (" . implode(', ', $_ids) . ")";
    $db->exec();
} else {
    echo "Error1:\n";
    print_r($res);
}


$request = array(
    'method' => 'GetWordstatReportList',
);
$res = yandex_req($request);
$open_reports = array();
foreach ($res['data'] as $rep) {
    if ($rep['StatusReport'] == 'Done')
        $open_reports[] = $rep['ReportID'];
}

$db->sql = "SELECT ws_rep_id FROM $dbws
            WHERE ws_rep_id != 0
                AND ws_weight = -1
            GROUP BY ws_rep_id";
$db->exec();
$reps = array();
while ($row = $db->fetch()) {
    if (in_array($row['ws_rep_id'], $open_reports)) {
        $request = array(
            'method' => 'GetWordstatReport',
            'param' => $row['ws_rep_id'],
        );
        $res = yandex_req($request);
        if (isset($res['data'])) {
            foreach ($res['data'] as $data) {
                $rep = array('word' => $data['Phrase'], 'weight' => 0, 'rep_id' => $row['ws_rep_id']);
                foreach ($data['SearchedWith'] as $item) {
                    $rep['weight'] += $item['Shows'];
                }
                $reps[] = $rep;
            }
        } else {
            echo "Error2 in {$row['ws_rep_id']}:\n";
            print_r($res);
        }
    }
}
$reps_to_del = array();
foreach ($reps as $rep) {
    $city = trim(str_replace(' достопримечательности', '', $rep['word']));
    $weight = intval($rep['weight']);
    $repid = intval($rep['rep_id']);
    $db->sql = "UPDATE $dbws SET ws_weight = '$weight' WHERE ws_rep_id = '$repid' AND ws_city_title = '$city'";
    $db->exec();
    $reps_to_del[$repid] = $repid;
}
foreach ($reps_to_del as $repdel) {
    yandex_req(array(
        'method' => 'DeleteWordstatReport',
        'param' => $repdel,
    ));
}

function yandex_req($request) {
    //$url = "https://api-sandbox.direct.yandex.ru/json-api/v4/";
    $url = "https://api.direct.yandex.ru/v4/json/";
    $request['locale'] = 'ru';
    $opts = array(
        'http' => array(
            'method' => "POST",
            'content' => json_encode($request),
        )
    );
    $context = stream_context_create($opts);
    stream_context_set_option($context, 'ssl', 'local_cert', _DIR_ROOT . '/data/private/api-yandex/solid-cert.crt');
    $result = @file_get_contents($url, 0, $context);

    return json_decode($result, true);

    /*
      print_r($request);
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      $response = curl_exec($ch);
      curl_close($ch);
      print_r($response);
     */
}

?>
