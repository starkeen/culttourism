<?php

session_start();
include('config/configuration.php');
if (_ER_REPORT) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}
include('includes/functions.php');
if (_ER_REPORT) {
    include('includes/debug.php');
}

include(_DIR_INCLUDES . '/class.Helper.php');
spl_autoload_register('Helper::autoloader');

$server_request_uri = urldecode($_SERVER['REQUEST_URI']);
if (strpos($server_request_uri, '?')) {
    $server_request_uri = mb_substr($server_request_uri, 0, strpos($server_request_uri, '?'), 'utf-8');
}
$request_uri_arr = explode('/', $server_request_uri);
if ($_SERVER['HTTP_HOST'] != _URL_ROOT) {
    $request_suburi_arr = explode('/', _URL_ROOT);
    if ($request_suburi_arr[1] == $request_uri_arr[1]) {
        array_shift($request_uri_arr);
    }
}
@list($host_id, $module_id, $page_id, $id, $id2) = $request_uri_arr;

$module_id = (isset($module_id) && strlen($module_id) != 0) ? urldecode($module_id) : _INDEXPAGE_URI;
if ($module_id == 'index') {
    $module_id = _INDEXPAGE_URI;
}
$page_id = isset($page_id) ? urlencode($page_id) : null;
$id = isset($id) ? urlencode($id) : null;
$id2 = isset($id) ? urlencode($id2) : null;

$smarty = new mySmarty($module_id);
$db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);
if (file_exists(_DIR_MODULES . "/$module_id/$module_id.php")) {
    include(_DIR_MODULES . "/$module_id/$module_id.php");
} else {
    include(_DIR_INCLUDES . '/class.Page.php');
}
$page = Page::getInstance($db, array($module_id, $page_id, $id, $id2));
$smarty->assign('page', $page);

header('X-Powered-By: html');
header('Content-Type: text/html; charset=utf-8');

if (_CACHE_DAYS != 0 && !$page->isAjax) {
    header('Expires: ' . $page->expiredate);
    header('Last-Modified: ' . $page->lastedit);
    header('Cache-Control: private, max-age=' . _CACHE_DAYS * 3600);

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
    header("Cache-control: public");
    header("Pragma: cache");
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
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Expires: " . date("r"));
    $page->lastedit = null;
}
$smarty->caching = false;
if (_ER_REPORT || isset($_GET['debug'])) {
    $smarty->assign('debug_info', $db->getDebugInfoText());
} else {
    $smarty->assign('debug_info', '');
}

if ($module_id == 'ajax') {
    $smarty->display(_DIR_TEMPLATES . '/_main/empty.sm.html');
} elseif ($module_id == 'api') {
    $smarty->display(_DIR_TEMPLATES . '/_main/api.html.sm.html');
} else {
    $smarty->display(_DIR_TEMPLATES . '/_main/main.html.sm.html');
}
exit();
?>