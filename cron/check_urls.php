<?php

$dbp = $db->getTableName('pagepoints');
$dbu = $db->getTableName('region_url');
$db->sql = "SELECT pt_id, pt_name, pt_citypage_id, pt_website
            FROM $dbp p
                LEFT JOIN $dbu url ON url.citypage = p.pt_citypage_id
            WHERE pt_website != ''";
$db->exec();
while ($row = $db->fetch()) {
    sleep(0.1);
}
?>
