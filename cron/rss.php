<?php

$filesRSS = [
    'blog.xml',
    'blog-dlvrit.xml',
    'blog-facebook.xml',
    'blog-twitter.xml',
    'blog-telegram.xml',
];

$be = new MBlogEntries($db);

$feed['title'] = 'Культурный туризм в России';
$feed['sitelink'] = _SITE_URL;
$feed['mail_editor'] = 'common@ourways.ru (OURWAYS.RU editor)';
$feed['mail_webmaster'] = 'starkeen@ourways.ru (Andrey Panisko)';
$feed['description'] = 'Достопримечательности России и ближнего зарубежья: музеи, церкви и монастыри, памятники архитектуры';
$feed['date_build'] = date('r');
$feed['generator'] = 'Cultural tourism / ' . _SITE_URL;


$smarty->assign('entries', $be->getLastActive(10));
$smarty->assign('feed', $feed);

$fileContent = $smarty->fetch(_DIR_TEMPLATES . '/_XML/rss.sm.xml');

foreach ($filesRSS as $fileType) {
    $fileName = sprintf('%s/feed/%s', _DIR_DATA, $fileType); //имя sitemap-файла
    //chmod($fileName, 0777);
    file_put_contents($fileName, $fileContent);
}

