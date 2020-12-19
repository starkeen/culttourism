<?php

declare(strict_types=1);

namespace app\core\application;

use app\core\module\ModuleFetcher;
use app\core\module\ModuleInterface;
use app\core\page\Content;
use app\core\page\Head;
use app\core\page\Headers;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\exceptions\AccessDeniedException;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;
use app\modules\BlogModule;
use app\modules\RedirectsModule;
use Auth;
use MRedirects;
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
     * @var SiteResponse
     */
    private $response;

    /**
     * @var WebUser
     */
    private $user;

    /**
     * @var ModuleFetcher
     */
    private $moduleFetcher;

    public function __construct()
    {
        parent::__construct();

        $this->request = new SiteRequest($_SERVER['REQUEST_URI']);
        $this->headers = new Headers();
        $this->content = new Content(new Head());
        $this->response = new SiteResponse($this->headers, $this->content);
        $this->user = new WebUser(new Auth($this->db));
        $modules =  [
            new RedirectsModule($this->db),
            new BlogModule($this->db),
        ];
        $this->moduleFetcher = new ModuleFetcher($this->db, $modules);
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

        $module = $this->moduleFetcher->getModule($this->request);

        $page = $this->moduleFetcher->getPageModule($this->request);
        $page->smarty = $this->templateEngine;
        $page->logger = $this->logger;
        $page->auth = $this->getUser()->getAuth();
        $page->webUser = $this->getUser();
        $page->pageHeaders = $this->headers;
        $page->pageContent = $this->content;
        $page->response = $this->response;

        $this->display($page, $module);

        exit();
    }

    /**
     * @param Page $page
     * @param ModuleInterface $module
     */
    private function display(Page $page, ModuleInterface $module): void
    {
        try {
            $page->init();
            $module->process($this->request, $this->response);
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

            $page->logger->error($exception->getMessage());
        }

        $this->headers->add('X-Powered-By: culttourism');
        $this->headers->add('Content-Type: text/html; charset=utf-8');

        if ($page->response->getLastEditTimestamp() > 0 && !$this->request->isAjax()) {
            $this->headers->add('Last-Modified: ' . gmdate('D, d M Y H:i:s', $page->response->getLastEditTimestamp()) . ' GMT');
            $this->headers->add('Cache-control: public');
            $this->headers->add('Pragma: cache');
            $this->headers->add('Expires: ' . gmdate('D, d M Y H:i:s', $page->response->getLastEditTimestamp() + 60 * 60 * 24 * 7) . ' GMT');
            if ($this->request->getHeader('If-Modified-Since') !== null) {
                $modifiedSince = explode(';', $this->request->getHeader('If-Modified-Since'));
                if (strtotime($modifiedSince[0]) >= $page->response->getLastEditTimestamp()) {
                    $this->headers->add('HTTP/1.1 304 Not Modified');
                    $this->headers->flush();
                    exit();
                }
            }
        } else {
            $this->headers->add('Cache-Control: no-store, no-cache, must-revalidate');
            $this->headers->add('Expires: ' . date('r'));
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
