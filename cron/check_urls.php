<?php

$dbp = $db->getTableName('pagepoints');
$dbu = $db->getTableName('region_url');
$db->sql = "SELECT pt_id, pt_name, pt_citypage_id, pt_website, url.url
            FROM $dbp p
                LEFT JOIN $dbu url ON url.citypage = p.pt_citypage_id
            WHERE pt_website != ''
            LIMIT 200";
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
    if (intval($codes[1]) != 200) {
        echo "Код ошибки $codes[1] в http://culttourism.ru{$row['url']}/object{$row['pt_id']}.html\n";
        $errlog[] = "$codes[1] - http://culttourism.ru{$row['url']}/object{$row['pt_id']}.html";
    }
    sleep(0.1);
}
?>
