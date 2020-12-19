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
use Page;
use Throwable;

class WebApplication extends Application
{
    /**
     * @var SiteRequest
     */
    private $request;

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
        $this->response = new SiteResponse(new Headers(), new Content(new Head()));
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
            $this->response->getHeaders()->add('HTTP/1.1 301 Moved Permanently');
            $this->response->getHeaders()->add('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->response->getHeaders()->flush();
            exit();
        }

        $module = $this->moduleFetcher->getModule($this->request);

        $page = $this->moduleFetcher->getPageModule($this->request);
        $page->smarty = $this->templateEngine;
        $page->logger = $this->logger;
        $page->auth = $this->getUser()->getAuth();
        $page->webUser = $this->getUser();
        $page->pageContent = $this->response->getContent();
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
            $this->response->getHeaders()->add('HTTP/1.1 301 Moved Permanently');
            $this->response->getHeaders()->add('Location: ' . $exception->getTargetUrl());
        } catch (NotFoundException $exception) {
            $this->logger->notice('Ошибка 404', [
                'srv' => $_SERVER ?? [],
            ]);

            $this->response->getHeaders()->add('Content-Type: text/html; charset=utf-8');
            $this->response->getHeaders()->add('HTTP/1.0 404 Not Found');

            $this->response->getContent()->getHead()->addTitleElement('404 Not Found - страница не найдена на сервере');
            $this->response->getContent()->setH1('Не найдено');
            $this->templateEngine->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->templateEngine->assign('host', _SITE_URL);
            $this->templateEngine->assign('suggestions', []);
            $this->response->getContent()->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/_errors/er404.sm.html'));
        } catch (AccessDeniedException $exception) {
            $this->logger->notice('Ошибка 403', [
                'srv' => $_SERVER ?? [],
            ]);

            $this->response->getHeaders()->add('Content-Type: text/html; charset=utf-8');
            $this->response->getHeaders()->add('HTTP/1.1 403 Forbidden');

            $this->response->getContent()->getHead()->addTitleElement('403 Forbidden - страница недоступна (запрещено)');
            $this->response->getContent()->setH1('Запрещено');
            $this->templateEngine->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->templateEngine->assign('host', _SITE_URL);
            $this->response->getContent()->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/_errors/er403.sm.html'));
        } catch (Throwable $exception) {
            $this->response->getHeaders()->add('Content-Type: text/html; charset=utf-8');
            $this->response->getHeaders()->add('Content-Type: text/html; charset=utf-8');
            $this->response->getHeaders()->add('HTTP/1.1 503 Service Temporarily Unavailable');
            $this->response->getHeaders()->add('Status: 503 Service Temporarily Unavailable');
            $this->response->getHeaders()->add('Retry-After: 300');

            $this->response->getContent()->getHead()->addTitleElement('Ошибка 503 - Сервис временно недоступен');
            $this->response->getContent()->setH1('Сервис временно недоступен');
            $this->response->getContent()->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/_errors/er503.sm.html'));

            $page->logger->error($exception->getMessage());
        }

        $this->response->getHeaders()->add('X-Powered-By: culttourism');
        $this->response->getHeaders()->add('Content-Type: text/html; charset=utf-8');

        if ($page->response->getLastEditTimestamp() > 0 && !$this->request->isAjax()) {
            $this->response->getHeaders()->add('Last-Modified: ' . gmdate('D, d M Y H:i:s', $page->response->getLastEditTimestamp()) . ' GMT');
            $this->response->getHeaders()->add('Cache-control: public');
            $this->response->getHeaders()->add('Pragma: cache');
            $this->response->getHeaders()->add('Expires: ' . gmdate('D, d M Y H:i:s', $page->response->getLastEditTimestamp() + 60 * 60 * 24 * 7) . ' GMT');
            if ($this->request->getHeader('If-Modified-Since') !== null) {
                $modifiedSince = explode(';', $this->request->getHeader('If-Modified-Since'));
                if (strtotime($modifiedSince[0]) >= $page->response->getLastEditTimestamp()) {
                    $this->response->getHeaders()->add('HTTP/1.1 304 Not Modified');
                    $this->response->getHeaders()->flush();
                    exit();
                }
            }
        } else {
            $this->response->getHeaders()->add('Cache-Control: no-store, no-cache, must-revalidate');
            $this->response->getHeaders()->add('Expires: ' . date('r'));
        }

        $this->response->getHeaders()->flush();

        if ($this->request->isAjax()) {
            echo $this->response->getContent()->getBody();
        } else {
            $this->templateEngine->assign('user', $this->getUser());
            $this->templateEngine->assign('pageContent', $this->response->getContent());

            $this->templateEngine->display(_DIR_TEMPLATES . '/_main/main.html.tpl');
        }
    }

    private function getUser(): WebUser
    {
        return $this->user;
    }
}
