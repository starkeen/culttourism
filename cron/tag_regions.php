<?php
//echo '<p>tag regions...';
$dbc = $db->getTableName('pagecity');
$dbu = $db->getTableName('region_url');
$db->sql = "SELECT city.pc_title as title, city.pc_rank, url.url
            FROM $dbc city
            LEFT JOIN $dbu url ON url.uid = city.pc_url_id
            WHERE pc_city_id != 0
            ORDER BY RAND()";
//$db->showSQL();

$res = $db->exec();
$entry = array();
while($row = $db->fetch()) {
    if ($row['pc_rank'] >= 500) $row['fontsize'] = '8pt';
    elseif ($row['pc_rank'] < 500 && $row['pc_rank'] >= 100) $row['fontsize'] = '7pt';
    elseif ($row['pc_rank'] < 100 && $row['pc_rank'] >= 500) $row['fontsize'] = '6pt';
    else $row['fontsize'] = '5pt';
    $row['color'] = '0x00BFFF';
    $row['hicolor'] = '0xFF0000';
    $row['class'] = 'citytag';
    $row['url'] = substr(_SITE_URL,0,-1) . $row['url'];
    $entry[] = $row;
}
$smarty->assign('entries', $entry);
//print_x($entry);

$filecontent = $smarty->fetch(_DIR_TEMPLATES . '/_XML/regions.sm.xml');
//echo $filecontent;
$filename=_DIR_ROOT . '/data/feed/regions.xml'; //имя sitemap-файла

chmod("$filename", 0777);
//echo "<p>Запись в $filename... ";
$file_hndlr = fopen("$filename", "w+");
if ($file_hndlr) {
    fwrite($file_hndlr, $filecontent);
    $filesize = filesize($filename);
    fclose($file_hndlr);
    //echo 'Файл записан!</p>';
}
else echo '<br>Ошибка доступа к файлу!';

//echo '<p>Записей: ' . count($entry) . '. Размер файла: '. $filesize . " байт.\n";
?>