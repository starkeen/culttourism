<?php

$dbc = $db->getTableName('pagecity');
$dbrs = $db->getTableName('ref_country');
$dbrr = $db->getTableName('ref_region');
$dbrc = $db->getTableName('ref_city');
$dbu = $db->getTableName('region_url');

$db->sql = "SELECT pc.pc_id, pc.pc_title,
                pc.pc_country_id, rs.name as country_name,
                pc.pc_region_id, pc_reg.pc_title as region_name, rr.name as region_name2, ru_reg.url as region_url,
                pc.pc_city_id, rc.name as city_name, ru_city.url as city_url
            FROM $dbc pc
                LEFT JOIN $dbrs rs ON rs.id = pc.pc_country_id
                LEFT JOIN $dbrr rr ON rr.id = pc.pc_region_id AND rr.country_id = pc.pc_country_id
                LEFT JOIN $dbrc rc ON rc.id = pc.pc_city_id AND rc.country_id = pc.pc_country_id AND rc.region_id = pc.pc_region_id

                LEFT JOIN $dbc pc_reg ON pc_reg.pc_region_id = pc.pc_region_id AND pc_reg.pc_city_id = 0 AND pc_reg.pc_country_id = pc.pc_country_id
                LEFT JOIN $dbc pc_cou ON pc_cou.pc_country_id = pc.pc_country_id AND pc_reg.pc_region_id = 0 AND pc_reg.pc_city_id = 0

                LEFT JOIN $dbu ru_cou  ON ru_cou.uid  = pc_cou.pc_url_id
                LEFT JOIN $dbu ru_reg  ON ru_reg.uid  = pc_reg.pc_url_id
                LEFT JOIN $dbu ru_city ON ru_city.uid = pc.pc_url_id

            WHERE pc.pc_pagepath IS NULL

            ORDER BY pc.pc_id";
$db->exec();

$out = [];
$url_root = '';
while ($row = $db->fetch()) {
    $out[$row['pc_id']] = '';
    //-------------- страна --
    if ($row['country_name']) {
        $out[$row['pc_id']] .= $row['country_name'];
    }
    //-------------- регион --
    if ($row['region_url'] && $row['region_name']) {
        $out[$row['pc_id']] .= " > <a href=\"$url_root{$row['region_url']}/\">{$row['region_name']}</a>";
    } elseif ($row['region_name']) {
        $out[$row['pc_id']] .= " > {$row['region_name']}";
    } elseif ($row['region_name2']) {
        $out[$row['pc_id']] .= " > {$row['region_name2']}";
    }
    //-------------- город --
    if ($row['city_url'] && $row['city_name']) {
        $out[$row['pc_id']] .= " > <a href=\"$url_root{$row['city_url']}/\" title=\"перейти к странице {$row['pc_title']}\">{$row['pc_title']}</a>";
    }
}

$pc = new MPageCities($db);
foreach ($out as $cid => $link) {
    $pc->updatePagepath($cid, $link);
}
