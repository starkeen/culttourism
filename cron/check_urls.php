<?php

ini_set('max_execution_time', 600);

$allow_codes = array(0, 200, 301, 302, 303, 304, 400);
$limit_once = 100;

$dbp = $db->getTableName('pagepoints');
$dbu = $db->getTableName('region_url');
$dbsp = $db->getTableName('siteprorerties');

$db->sql = "SELECT sp_value FROM $dbsp WHERE sp_id = 25";
$db->exec();
$row = $db->fetch();
$lim_shift = $row['sp_value'];

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
    $out = curl_exec($curl);
    curl_close($curl);
    $out = explode("\n", $out);
    $codes = explode(' ', $out[0]);
    $code = intval($codes[1]);
    if (!in_array($code, $allow_codes)) {
        $errlog[] = str_replace(array("\n", "\r", "\t"), '', "$codes[1]: {$row['pt_website']} - {$row['pt_name']} {$out[0]} (http://culttourism.ru{$row['url']}/)");
    }
}

$db->sql = "SELECT COUNT(*) AS cnt FROM $dbp WHERE pt_website != ''";
$db->exec();
$row = $db->fetch();
$lim_total = intval($row['cnt']);

$newshift = $lim_shift + $limit_once;
if ($newshift >= $lim_total)
    $newshift = 0;
$db->sql = "UPDATE $dbsp SET sp_value = '$newshift' WHERE sp_id = 25";
$db->exec();

if (count($errlog) > 0)
    echo implode("\n", $errlog);
?>
