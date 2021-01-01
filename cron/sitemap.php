<?php

$baseurl = 'https://' . GLOBAL_URL_ROOT;
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
    $url['full'] = "$baseurl/{$row['md_url']}";
    if ($row['md_url'] !== 'index.html') {
        $url['full'] .= '/';
    }
    $url['lastmod'] = $basedate;
    $url['freq'] = 'weekly';
    $url['priority'] = ($row['md_url'] !== 'index.html') ? '0.90' : '1.00';
    $urls[] = $url;
}
$db->sql = "SELECT u.url, DATE_FORMAT(c.pc_lastup_date, '%Y-%m-%dT%H:%i:%s+00:00') dateup
            FROM $dbr u
                LEFT JOIN $dbc c ON c.pc_id = u.citypage
            WHERE c.pc_text is not null";
$db->exec();
while ($row = $db->fetch()) {
    $url['uri'] = $row['url'];
    $url['full'] = "$baseurl{$row['url']}/";
    $url['lastmod'] = $row['dateup'];
    $url['freq'] = 'weekly';
    $url['priority'] = '0.80';
    $urls[] = $url;
}

$db->sql = "SELECT concat(u.url, '/', p.pt_slugline, '.html') url, DATE_FORMAT(p.pt_lastup_date, '%Y-%m-%dT%H:%i:%s+00:00') dateup
            FROM $dbp p
                LEFT JOIN $dbc c ON c.pc_id = p.pt_citypage_id
                    LEFT JOIN $dbr u ON u.uid = c.pc_url_id
            WHERE pt_active = 1";
$db->exec();
while ($row = $db->fetch()) {
    $url['uri'] = $row['url'];
    $url['full'] = "$baseurl{$row['url']}";
    $url['lastmod'] = $row['dateup'];
    $url['freq'] = 'monthly';
    $url['priority'] = '0.70';
    $urls[] = $url;
}

$smarty->assign('urls', $urls);

$filecontent = $smarty->fetch(_DIR_TEMPLATES . '/_XML/sitemap.sm.xml');
$filename = GLOBAL_DIR_ROOT . '/sitemap.xml'; //имя sitemap-файла

$filesize_old = filesize($filename);

chmod($filename, 0777);
$file_hndlr = fopen((string) $filename, 'wb+');
if ($file_hndlr) {
    fwrite($file_hndlr, $filecontent);
    fclose($file_hndlr);
} else {
    echo '<br>Ошибка доступа к файлу!';
}

$filesize = filesize($filename);

if ($filesize !== $filesize_old) {
    $ch = curl_init();
    $url = 'http://www.google.com/webmasters/sitemaps/ping?sitemap=http://culttourism.ru/sitemap.xml';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $answer = curl_exec($ch);
    curl_close($ch);
}
