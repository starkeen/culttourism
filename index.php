<?php

use app\db\FactoryDB;
use app\sys\TemplateEngine;

session_start();
include('config/configuration.php');
error_reporting(E_ALL & ~E_DEPRECATED);
if (_ER_REPORT) {
    ini_set('display_errors', true);
} else {
    ini_set('display_errors', false);
}

include _DIR_ROOT . '/vendor/autoload.php';

$sentryClient = new Raven_Client(SENTRY_DSN);
$sentryClient->install();

if (!_ER_REPORT && (!isset($_SERVER['HTTP_X_HTTPS']) || $_SERVER['HTTP_X_HTTPS'] == '') && false) {
    //Redirect all to HTTPS
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

$server_request_uri = urldecode($_SERVER['REQUEST_URI']);
if (strpos($server_request_uri, '?')) {
    $server_request_uri = mb_substr($server_request_uri, 0, strpos($server_request_uri, '?'), 'utf-8');
}
$request_uri_arr = explode('/', $server_request_uri);
if ($_SERVER['HTTP_HOST'] !== _URL_ROOT) {
    $request_suburi_arr = explode('/', _URL_ROOT);
    if (isset($request_suburi_arr[1]) && isset($request_uri_arr[1]) && $request_suburi_arr[1] == $request_uri_arr[1]) {
        array_shift($request_uri_arr);
    }
}
if ($request_uri_arr[1] == '' && !empty($request_uri_arr[2])) {
    unset($request_uri_arr[1]);
    $canonical = implode('/', $request_uri_arr);
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: $canonical");
    exit();
}
@list($host_id, $module_id, $page_id, $id, $id2) = array_values($request_uri_arr);

$module_id = (isset($module_id) && strlen($module_id) !== 0) ? urldecode($module_id) : _INDEXPAGE_URI;
if ($module_id === 'index') {
    $module_id = _INDEXPAGE_URI;
}
$page_id = isset($page_id) ? urlencode($page_id) : null;
$id = isset($id) ? urlencode($id) : null;
$id2 = isset($id) ? urlencode($id2) : null;

$smarty = new TemplateEngine();
$db = FactoryDB::db();

$sp = new MSysProperties($db);
$releaseKey = $sp->getByName('git_hash');
$sentryClient->setRelease($releaseKey);

$includeModulePath = _DIR_INCLUDES . '/class.Page.php';
$customModulePath = sprintf('%s/%s/%s.php', _DIR_MODULES, $module_id, $module_id);
if (file_exists($customModulePath)) {
    $includeModulePath = $customModulePath;
}
include($includeModulePath);

$page = Page::getInstance($db, array($module_id, $page_id, $id, $id2));
$smarty->assign('page', $page);

header('X-Powered-By: html');
header('Content-Type: text/html; charset=utf-8');

if (_CACHE_DAYS !== 0 && !$page->isAjax) {
    header('Expires: ' . $page->expiredate);
    header('Last-Modified: ' . $page->lastedit);
    header('Cache-Control: public, max-age=' . _CACHE_DAYS * 3600);

    $headers = getallheaders();
    if (isset($headers['If-Modified-Since'])) {
        // Разделяем If-Modified-Since (Netscape < v6 отдаёт их неправильно)
        $modifiedSince = explode(';', $headers['If-Modified-Since']);
        // Преобразуем запрос клиента If-Modified-Since в таймштамп
        $modifiedSince = strtotime($modifiedSince[0]);
        $lastModified = strtotime($page->lastedit);
        // Сравниваем время последней модификации контента с кэшем клиента
        if ($lastModified <= $modifiedSince) {
            // Разгружаем канал передачи данных!
            header('HTTP/1.1 304 Not Modified');
            exit();
        }
    }
} elseif ($page->lastedit_timestamp > 0 && !$page->isAjax) {
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $page->lastedit_timestamp) . ' GMT');
    header('Cache-control: public');
    header('Pragma: cache');
    header('Expires: ' . gmdate('D, d M Y H:i:s', $page->lastedit_timestamp + 60 * 60 * 24 * 7) . ' GMT');
    $headers = getallheaders();
    if (isset($headers['If-Modified-Since'])) {
        $modifiedSince = explode(';', $headers['If-Modified-Since']);
        if (strtotime($modifiedSince[0]) >= $page->lastedit_timestamp) {
            header('HTTP/1.1 304 Not Modified');
            exit();
        }
    }
} else {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Expires: ' . date('r'));
    $page->lastedit = null;
}
$smarty->caching = false;
if (_ER_REPORT || isset($_GET['debug'])) {
    $smarty->assign('debug_info', $db->getDebugInfoText());
} else {
    $smarty->assign('debug_info', '');
}

if ($page->isAjax) {
    echo $page->content;
} elseif ($module_id === 'api') {
    $smarty->display(_DIR_TEMPLATES . '/_main/api.html.sm.html');
} else {
    $smarty->display(_DIR_TEMPLATES . '/_main/main.html.sm.html');
}

exit();
