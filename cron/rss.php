<?php

//echo '<p>rss...';
$dbb = $db->getTableName('blogentries');
$dbu = $db->getTableName('users');
$db->sql = "SELECT bg.br_id, bg.br_title, bg.br_text, 'Роберт' AS us_name,
            DATE_FORMAT(bg.br_date,'%a, %d %b %Y %H:%i:%s GMT') as bg_pubdate,
            DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
            IF(bg.br_url != '', CONCAT('" . _SITE_URL . "blog/',DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'), CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')) as br_link
            FROM $dbb bg
            LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
            WHERE br_active = '1' AND br_date < now()
            ORDER BY bg.br_date DESC
            LIMIT 10";
//$db->showSQL();

$res = $db->exec();
$entry = array();
while ($row = $db->fetch()) {
    $entry[$row['br_id']] = $row;
}
$smarty->assign('entries', $entry);
//print_x($entry);

$feed['title'] = 'Культурный туризм В России';
$feed['sitelink'] = _SITE_URL;
$feed['mail_editor'] = 'common@ourways.ru (OURWAYS.RU editor)';
$feed['mail_webmaster'] = 'starkeen@ourways.ru (Andrey Panisko)';
$feed['description'] = 'Достопримечательности России и ближнего зарубежья: музеи, церкви и монастыри, памятники архитектуры';
$feed['date_build'] = date('r');
$feed['generator'] = 'Cultural tourism / ' . _SITE_URL;
$smarty->assign('feed', $feed);

$filecontent = $smarty->fetch(_DIR_TEMPLATES . '/_XML/rss.sm.xml');
//echo $filecontent;
$filename = _DIR_ROOT . '/data/feed/blog.xml'; //имя sitemap-файла

chmod("$filename", 0777);
//echo "<p>Запись в $filename... ";
$file_hndlr = fopen("$filename", "w+");
if ($file_hndlr) {
    fwrite($file_hndlr, $filecontent);
    $filesize = filesize($filename);
    fclose($file_hndlr);
    //echo 'Файл записан!</p>';
}
else
    echo '<br>Ошибка доступа к файлу!';

//echo '<p>Записей: ' . count($entry) . '. Размер файла: '. $filesize . " байт.\n";
?>