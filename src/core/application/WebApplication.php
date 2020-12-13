<?php

declare(strict_types=1);

namespace app\core\application;

use app\core\page\Content;
use app\core\page\Headers;
use app\core\SiteRequest;
use Auth;
use Page;

class WebApplication extends Application
{
    /**
     * @var SiteRequest
     */
    private $request;

    /**
     * @var Headers
     */
    private $headers;

    /**
     * @var Content
     */
    private $content;

    public function __construct()
    {
        parent::__construct();

        $this->request = new SiteRequest($_SERVER['REQUEST_URI']);
        $this->headers = new Headers();
        $this->content = new Content();
    }

    public function init(): void
    {
        session_start();
        parent::init();
    }

    public function run(): void
    {
        $this->init();

        // редиректим на https
        if (!_ER_REPORT && (!isset($_SERVER['HTTP_X_HTTPS']) || $_SERVER['HTTP_X_HTTPS'] === '')) {
            $this->headers->add('HTTP/1.1 301 Moved Permanently');
            $this->headers->add('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->headers->flush();
            exit();
        }

        $module_id = $this->request->getModuleKey();

        $includeModulePath = _DIR_INCLUDES . '/class.Page.php';
        $customModulePath = sprintf('%s/%s/%s.php', _DIR_MODULES, $module_id, $module_id);
        if (file_exists($customModulePath)) {
            $includeModulePath = $customModulePath;
        }
        include($includeModulePath);

        $page = Page::getInstance($this->db, $this->request);
        $page->smarty = $this->smarty;
        $page->logger = $this->logger;
        $page->auth = new Auth($this->db);
        $page->pageHeaders = $this->headers;
        $page->pageContent = $this->content;

        $this->headers->add('X-Powered-By: html');
        $this->headers->add('Content-Type: text/html; charset=utf-8');

        if (_CACHE_DAYS !== 0 && !$this->request->isAjax()) {
            $this->headers->add('Expires: ' . $page->expiredate);
            $this->headers->add('Last-Modified: ' . $page->lastedit);
            $this->headers->add('Cache-Control: public, max-age=' . _CACHE_DAYS * 3600);

            $headers = getallheaders();
            if (isset($headers['If-Modified-Since'])) {
                // Разделяем If-Modified-Since (Netscape < v6 отдаёт их неправильно)
                $modifiedSince = explode(';', $headers['If-Modified-Since']);
                // Преобразуем запрос клиента If-Modified-Since в таймштамп
                $modifiedSince = strtotime($modifiedSince[0]);
                $lastModified = strtotime($page->lastedit);
                // Сравниваем время последней модификации контента с кэшем клиента
                if ($lastModified <= $modifiedSince) {
                    $this->headers->add('HTTP/1.1 304 Not Modified');
                    $this->headers->flush();
                    exit();
                }
            }
        } elseif ($page->lastedit_timestamp > 0 && !$this->request->isAjax()) {
            $this->headers->add('Last-Modified: ' . gmdate('D, d M Y H:i:s', $page->lastedit_timestamp) . ' GMT');
            $this->headers->add('Cache-control: public');
            $this->headers->add('Pragma: cache');
            $this->headers->add('Expires: ' . gmdate('D, d M Y H:i:s', $page->lastedit_timestamp + 60 * 60 * 24 * 7) . ' GMT');
            $headers = getallheaders();
            if (isset($headers['If-Modified-Since'])) {
                $modifiedSince = explode(';', $headers['If-Modified-Since']);
                if (strtotime($modifiedSince[0]) >= $page->lastedit_timestamp) {
                    $this->headers->add('HTTP/1.1 304 Not Modified');
                    $this->headers->flush();
                    exit();
                }
            }
        } else {
            $this->headers->add('Cache-Control: no-store, no-cache, must-revalidate');
            $this->headers->add('Expires: ' . date('r'));
            $page->lastedit = null;
        }

        $this->headers->flush();
        $page->display();

        exit();
    }
}
