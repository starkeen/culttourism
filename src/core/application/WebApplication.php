<?php

declare(strict_types=1);

namespace app\core\application;

use app\core\page\Content;
use app\core\page\Head;
use app\core\page\Headers;
use app\core\SiteRequest;
use app\core\WebUser;
use app\exceptions\AccessDeniedException;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;
use Auth;
use Page;
use Throwable;

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

    /**
     * @var WebUser
     */
    private $user;

    public function __construct()
    {
        parent::__construct();

        $this->request = new SiteRequest($_SERVER['REQUEST_URI']);
        $this->headers = new Headers();
        $this->content = new Content(new Head());
        $this->user = new WebUser(new Auth($this->db));
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
        $page->smarty = $this->templateEngine;
        $page->logger = $this->logger;
        $page->auth = $this->getUser()->getAuth();
        $page->webUser = $this->getUser();
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

        $this->display($page);

        exit();
    }

    /**
     * @param Page $page
     */
    private function display(Page $page): void
    {
        try {
            $page->init();
            $page->compileContent();
        } catch (RedirectException $exception) {
            $this->headers->add('HTTP/1.1 301 Moved Permanently');
            $this->headers->add('Location: ' . $exception->getTargetUrl());
        } catch (NotFoundException $exception) {
            $this->logger->notice('Ошибка 404', [
                'srv' => $_SERVER ?? [],
            ]);

            $this->headers->add('Content-Type: text/html; charset=utf-8');
            $this->headers->add('HTTP/1.0 404 Not Found');

            $this->content->getHead()->addTitleElement('404 Not Found - страница не найдена на сервере');
            $this->content->setH1('Не найдено');
            $this->templateEngine->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->templateEngine->assign('host', _SITE_URL);
            $this->templateEngine->assign('suggestions', []);
            $this->content->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/_errors/er404.sm.html'));
        } catch (AccessDeniedException $exception) {
            $this->logger->notice('Ошибка 403', [
                'srv' => $_SERVER ?? [],
            ]);

            $this->headers->add('Content-Type: text/html; charset=utf-8');
            $this->headers->add('HTTP/1.1 403 Forbidden');

            $this->content->getHead()->addTitleElement('403 Forbidden - страница недоступна (запрещено)');
            $this->content->setH1('Запрещено');
            $this->templateEngine->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->templateEngine->assign('host', _SITE_URL);
            $this->content->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/_errors/er403.sm.html'));
        } catch (Throwable $exception) {
            $this->headers->add('Content-Type: text/html; charset=utf-8');
            $this->headers->add('Content-Type: text/html; charset=utf-8');
            $this->headers->add('HTTP/1.1 503 Service Temporarily Unavailable');
            $this->headers->add('Status: 503 Service Temporarily Unavailable');
            $this->headers->add('Retry-After: 300');

            $this->content->getHead()->addTitleElement('Ошибка 503 - Сервис временно недоступен');
            $this->content->setH1('Сервис временно недоступен');
            $this->content->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/_errors/er503.sm.html'));
        }

        $this->headers->flush();

        if ($this->request->isAjax()) {
            echo $this->content->getBody();
        } else {
            $this->templateEngine->assign('user', $this->getUser());
            $this->templateEngine->assign('pageContent', $this->content);

            $this->templateEngine->display(_DIR_TEMPLATES . '/_main/main.html.tpl');
        }
    }

    private function getUser(): WebUser
    {
        return $this->user;
    }
}
