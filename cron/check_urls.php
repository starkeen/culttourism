<?php

ini_set('max_execution_time', 1500);

$allow_codes = array(200, 302);
$limit_once = 50;
$useragent = "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17 AlexaToolbar/alxg-3.1";

$dbp = $db->getTableName('pagepoints');
$dbu = $db->getTableName('region_url');
$dbsp = $db->getTableName('siteprorerties');

$db->sql = "SELECT count(*) AS cnt FROM $dbp WHERE pt_website != ''";
$db->exec();
$xrow = $db->fetch();
$lim_total = intval($xrow['cnt']);

$db->sql = "SELECT sp_value FROM $dbsp WHERE sp_id = 25";
$db->exec();
$row = $db->fetch();
$lim_shift = intval($row['sp_value']);

$db->sql = "SELECT pt_id, pt_name, pt_citypage_id, pt_website, url.url
            FROM $dbp p
                LEFT JOIN $dbu url ON url.citypage = p.pt_citypage_id
            WHERE pt_website != ''
            ORDER BY url.url
            LIMIT $lim_shift, $limit_once";
$db->exec();
$errlog = array();
while ($row = $db->fetch()) {
    /*
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $row['pt_website']);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_NOBODY, true);
      curl_setopt($curl, CURLOPT_HEADER, true);
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
      $out = curl_exec($curl);
      $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      curl_close($curl);
     */
    $url = $row['pt_website'];
    $ch = curl_init("http://check-host.net/check-http?host=$url");
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);
    $page_text = substr($content, strpos($content, 'get_check_results('));
    $page_text = substr($page_text, 0, strpos($page_text, 'check_displayer'));

    $keys = explode(',', trim(str_replace('get_check_results(', '', trim($page_text)), "'"));
    $key = str_replace(array(" ", "\n", "\t", "\r", "'"), '', trim(array_shift($keys)));
    $xslaves = explode('","', trim(implode(',', $keys), ',[]"'));
    array_shift($xslaves);
    $_postslaves = array();
    foreach ($xslaves as $xslave) {
        $_postslaves[] = "slaves[]=$xslave";
    }
    $postslaves = implode('&', $_postslaves);

    $ch = curl_init("http://check-host.net/check_result/$key");
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_REFERER, "http://check-host.net/check-http?host=$url");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postslaves);
    $content = curl_exec($ch);
    curl_close($ch);
    $states = array(200 => 0);
    foreach (json_decode($content) as $out) {
        $state = $out[0][3];
        echo "$state - $url\n";
        if (!isset($states[$state]))
            $states[$state] = 0;
        //if ($state != '')
        $states[$state]++;
    }
    ksort($states);
    $http_status = array_search(max($states), $states);

    if (!in_array($http_status, $allow_codes)) {
        $errlog[] = str_replace(array("\n", "\r", "\t"), '', "$http_status: {$row['pt_website']} - {$row['pt_name']} (http://culttourism.ru{$row['url']}/)");
    }
}

$newshift = $lim_shift + $limit_once;
if ($newshift >= $lim_total)
    $newshift = 0;

$db->sql = "UPDATE $dbsp SET sp_value = '$newshift' WHERE sp_id = 25";
$db->exec();

if (count($errlog) > 0)
    echo implode("\n", $errlog);
?>
