<?php

ini_set('max_execution_time', 1500);

$allow_codes = array(200, 301, 302, 303, 304);
$limit_once = 80;

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
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $row['pt_website']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
    $out = curl_exec($curl);
    curl_close($curl);
    $xout = explode("\n", $out);
    $codes = explode(' ', $xout[0]);
    $code = intval($codes[1]);
    if (!in_array($code, $allow_codes)) {
        $errlog[] = str_replace(array("\n", "\r", "\t"), '', "$codes[1]: {$row['pt_website']} - {$row['pt_name']} {$xout[0]} (http://culttourism.ru{$row['url']}/)");
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
