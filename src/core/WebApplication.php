<?php

declare(strict_types=1);

namespace app\core;

use MSysProperties;
use Page;

class WebApplication extends Application
{
    /**
     * @var SiteRequest
     */
    private $request;

    public function __construct()
    {
        parent::__construct();

        $this->request = new SiteRequest($_SERVER['REQUEST_URI']);
    }

    public function run(): void
    {
        session_start();

        // редиректим на https
        if (!_ER_REPORT && (!isset($_SERVER['HTTP_X_HTTPS']) || $_SERVER['HTTP_X_HTTPS'] === '')) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit();
        }

        $sp = new MSysProperties($this->db);
        $releaseKey = $sp->getByName('git_hash');
        $this->logger->setReleaseKey($releaseKey);

        $request = new SiteRequest($_SERVER['REQUEST_URI']);

        $module_id = $request->getModuleKey();

        $includeModulePath = _DIR_INCLUDES . '/class.Page.php';
        $customModulePath = sprintf('%s/%s/%s.php', _DIR_MODULES, $module_id, $module_id);
        if (file_exists($customModulePath)) {
            $includeModulePath = $customModulePath;
        }
        include($includeModulePath);

        $page = Page::getInstance($this->db, $this->request);

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

        $page->display();

        exit();
    }
}
