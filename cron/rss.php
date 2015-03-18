<?php

$be = new MBlogEntries($db);
$smarty->assign('entries', $be->getLastActive(10));
//print_x($entry);

$feed['title'] = 'Культурный туризм в России';
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
} else {
    echo '<br>Ошибка доступа к файлу!';
}
