<?php

$urls = array();
//echo '<p>sitemap...';

$baseurl = _URL_ROOT;
$basedate = date('Y-m-d\TH:i:s+00:00');
$urls = array();

$dbm = $db->getTableName('modules');
$dbr = $db->getTableName('region_url');
$dbp = $db->getTableName('pagepoints');
$dbc = $db->getTableName('pagecity');

$db->sql = "SELECT md_url FROM $dbm WHERE md_active = '1' AND md_robots = 'index, follow'";
$db->exec();
while ($row = $db->fetch()) {
    $url['uri'] = $row['md_url'];
    $url['full'] = "http://$baseurl/{$row['md_url']}";
    if ($row['md_url'] != 'index.html')
        $url['full'] .= '/';
    $url['lastmod'] = $basedate;
    $url['freq'] = 'daily';
    $url['priority'] = ($row['md_url'] != 'index.html') ? '0.90' : '1.00';
    $urls[] = $url;
}
$db->sql = "SELECT u.url, DATE_FORMAT(c.pc_lastup_date, '%Y-%m-%dT%H:%i:%s+00:00') dateup
            FROM $dbr u
            LEFT JOIN $dbc c ON c.pc_id = u.citypage
            WHERE c.pc_text is not null";
//$db->showSQL();
$db->exec();
while ($row = $db->fetch()) {
    $url['uri'] = $row['url'];
    $url['full'] = "http://$baseurl{$row['url']}/";
    $url['lastmod'] = $row['dateup'];
    $url['freq'] = 'daily';
    $url['priority'] = '0.80';
    $urls[] = $url;
}

$db->sql = "SELECT concat(u.url,'/object',p.pt_id,'.html') url, DATE_FORMAT(p.pt_lastup_date, '%Y-%m-%dT%H:%i:%s+00:00') dateup
            FROM $dbp p
            LEFT JOIN $dbc c ON c.pc_id = p.pt_citypage_id
            LEFT JOIN $dbr u ON u.uid = c.pc_url_id";
$db->exec();
while ($row = $db->fetch()) {
    $url['uri'] = $row['url'];
    $url['full'] = "http://$baseurl{$row['url']}";
    $url['lastmod'] = $row['dateup'];
    $url['freq'] = 'daily';
    $url['priority'] = '0.70';
    $urls[] = $url;
}

$smarty->assign('urls', $urls);

$filecontent = $smarty->fetch(_DIR_TEMPLATES . '/_XML/sitemap.sm.xml');
///echo $filecontent;
$filename = _DIR_ROOT . '/sitemap.xml'; //имя sitemap-файла

$filesize_old = filesize($filename);

chmod("$filename", 0777);
//echo "<p>Запись в $filename... ";
$file_hndlr = fopen("$filename", "w+");
if ($file_hndlr) {
    fwrite($file_hndlr, $filecontent);
    fclose($file_hndlr);
    //echo 'Файл записан!</p>';
}
else
    echo '<br>Ошибка доступа к файлу!';

$filesize = filesize($filename);

if ($filesize != $filesize_old) {
    $ch = curl_init();
    $url = 'http://www.google.com/webmasters/sitemaps/ping?sitemap=http://culttourism.ru/sitemap.xml';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $answer = curl_exec($ch);
    curl_close($ch);
    //echo $answer;
}


//echo '<p>Записей: ' . count($urls) . '. Размер файла: '. $filesize . " байт.\n";
?>