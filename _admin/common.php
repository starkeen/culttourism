<?php

error_reporting(E_ALL);
session_start();
/* Общие функции и опции */
require_once('../config/configuration.php');
require_once('debug.php');
require_once('class.mySmarty.php');
require_once('class.myDB.php');
require_once('class.Auth.php');
require_once('functions.php');

$db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);
$ticket = new Auth($db);
$ticket->checkSession('admin');

$script = explode('/', $_SERVER['PHP_SELF']);

if (isset($_SESSION['auth']) && $ticket->checkKey($_SESSION['auth']))
    $ticket->refreshKey($_SESSION['auth']);
elseif (!in_array('login.php', $script))
    header('Location: login.php');

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/html; charset=utf-8");

$isAdmin = false;
if (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] == 1)
    $isAdmin = true;

$adm_menu_items[] = array('link' => 'modules.php', 'title' => 'Страницы и модули', 'ico' => 'ico.a_modules.gif');
$adm_menu_items[] = array('link' => 'blog.php', 'title' => 'Записи в блоге', 'ico' => 'ico.a_modules.gif');
$adm_menu_items[] = array('link' => 'counters.php', 'title' => 'Счетчики', 'ico' => 'ico.a_counters.gif');
$adm_menu_items[] = array('link' => 'nogps.php', 'title' => 'Точки без координат', 'ico' => 'ico.a_modules.gif');
$adm_menu_items[] = array('link' => 'stat_search.php', 'title' => 'Статистика поиска', 'ico' => 'ico.a_modules.gif');
$adm_menu_items[] = array('link' => 'stat_yandex.php', 'title' => 'Статистика Яндекса', 'ico' => 'ico.a_modules.gif');
if ($isAdmin) {
    $adm_menu_items[] = array('link' => 'users.php', 'title' => 'Пользователи', 'ico' => 'ico.a_users.gif');
    $adm_menu_items[] = array('link' => 'settings.php', 'title' => 'Настройки сайта', 'ico' => 'ico.a_refs.gif');
    $adm_menu_items[] = array('link' => 'cron.php', 'title' => 'Задачи по расписанию', 'ico' => 'ico.a_refs.gif');
}
$adm_menu_items[] = array('link' => 'log_error.php', 'title' => 'Журнал ошибок', 'ico' => 'ico.a_logs.gif');

$smarty = new mySmarty();

$smarty->assign('adm_menu', $adm_menu_items);
if (isset($_SESSION['user_name']))
    $smarty->assign('adm_user', $_SESSION['user_name']);
else
    $smarty->assign('adm_user', '');
$smarty->assign('site_url', _URL_ROOT);
?>