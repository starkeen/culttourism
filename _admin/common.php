<?php

use app\db\FactoryDB;
use app\sys\SentryLogger;
use app\sys\TemplateEngine;

error_reporting(E_ALL);
ini_set('display_errors', 'Off');
ini_set('memory_limit', '512M');
session_start();

include dirname(__DIR__) . '/vendor/autoload.php';
include dirname(__DIR__) . '/config/configuration.php';

SentryLogger::init();

if (!_ER_REPORT && (!isset($_SERVER['HTTP_X_HTTPS']) || $_SERVER['HTTP_X_HTTPS'] == '')) {
    header("HTTP/1.1 301 Moved Permanently");
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

$db = FactoryDB::db();
$ticket = new Auth($db);
$ticket->checkSession('admin');

$script = explode('/', $_SERVER['PHP_SELF']);
$uri_array = explode('/', $_SERVER['REQUEST_URI']);
$requesturi = isset($_SERVER['REQUEST_URI']) ? urlencode(array_pop($uri_array)) : '';

if (isset($_SESSION['auth']) && $ticket->checkKey($_SESSION['auth'])) {
    $ticket->refreshKey($_SESSION['auth']);
} elseif (!in_array('login.php', $script, true)) {
    header("Location: login.php?r=$requesturi");
    exit();
}

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/html; charset=utf-8");

$isAdmin = false;
if (isset($_SESSION['user_admin']) && (int) $_SESSION['user_admin'] === 1) {
    $isAdmin = true;
}

$adm_menu_items[] = array('link' => 'modules.php', 'title' => 'Страницы и модули', 'ico' => 'ico.a_modules.gif');
$adm_menu_items[] = array('link' => 'points.php', 'title' => 'Точки', 'ico' => 'ico.a_refs.gif');
$adm_menu_items[] = array('link' => 'parser.php', 'title' => 'Парсер', 'ico' => 'ico.a_refs.gif');
$adm_menu_items[] = array('link' => 'photos.php', 'title' => 'Фото', 'ico' => 'ico.a_refs.gif');
$adm_menu_items[] = array('link' => 'addpoints.php', 'title' => 'Заявки', 'ico' => 'ico.a_refs.gif');
$adm_menu_items[] = array('link' => 'lists.php', 'title' => 'Списки', 'ico' => 'ico.a_refs.gif');
$adm_menu_items[] = array('link' => 'stat_yandex.php', 'title' => 'Статистика Яндекса', 'ico' => 'ico.a_modules.gif');
if ($isAdmin) {
    $adm_menu_items[] = array('link' => 'users.php', 'title' => 'Пользователи', 'ico' => 'ico.a_users.gif');
    $adm_menu_items[] = array('link' => 'settings.php', 'title' => 'Настройки сайта', 'ico' => 'ico.a_refs.gif');
}

$smarty = new TemplateEngine();

$smarty->assign('adm_menu', $adm_menu_items);
if (isset($_SESSION['user_name'])) {
    $smarty->assign('adm_user', $_SESSION['user_name']);
} else {
    $smarty->assign('adm_user', '');
}
$smarty->assign('site_url', _URL_ROOT);
