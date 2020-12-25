<?php

declare(strict_types=1);

namespace app\core\application;

use app\core\GlobalConfig;
use app\core\module\ModuleFetcher;
use app\core\page\Content;
use app\core\page\Head;
use app\core\page\Headers;
use app\core\SessionStorage;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\exceptions\AccessDeniedException;
use app\exceptions\BaseApplicationException;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;
use app\modules\AjaxModule;
use app\modules\ApiModule;
use app\modules\BlogModule;
use app\modules\CityModule;
use app\modules\CoreModule;
use app\modules\DefaultModule;
use app\modules\FeedbackModule;
use app\modules\ListModule;
use app\modules\MainPageModule;
use app\modules\MapModule;
use app\modules\RedirectsModule;
use app\modules\SearchModule;
use app\modules\SignModule;
use app\modules\SysModule;
use Auth;
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

    /**
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @var SessionStorage
     */
    private $session;

    public function __construct()
    {
        parent::__construct();

        $this->session = new SessionStorage();
        $this->request = new SiteRequest($_SERVER['REQUEST_URI']);
        $this->response = new SiteResponse(new Headers(), new Content(new Head()));
        $this->user = new WebUser(new Auth($this->db), $this->session);
        $this->globalConfig = new GlobalConfig($this->db);
        $modules =  [
            new RedirectsModule($this->db),
            new MainPageModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
            new AjaxModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
            new MapModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
            new ListModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
            new CityModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
            new SearchModule($this->db, $this->templateEngine, $this->user, $this->globalConfig, $this->logger),
            new BlogModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
            new FeedbackModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
            new SignModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
            new SysModule($this->db, $this->templateEngine, $this->user, $this->globalConfig, $this->logger),
            new ApiModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
            new DefaultModule($this->db, $this->templateEngine, $this->user, $this->globalConfig),
        ];
        $this->moduleFetcher = new ModuleFetcher($modules);
    }

    public function init(): void
    {
        $this->session->start();
        parent::init();
    }

    public function run(): void
    {
        $this->init();

        // редиректим на https
        if (!_ER_REPORT && !$this->request->isSSL()) {
            $this->response->getHeaders()->sendRedirect($this->request->getCurrentURL(), true);
        }

        try {
            $module = $this->moduleFetcher->getModule($this->request);

            $this->response->getContent()->getHead()->setTitleDelimiter($this->globalConfig->getTitleDelimiter());
            $this->response->getContent()->setUrlRss($this->globalConfig->getUrlRSS());
            $this->response->getContent()->setJsResources($this->globalConfig->getJsResources());
            $this->response->getContent()->setUrlCss($this->globalConfig->getUrlCss());
            $this->response->getContent()->setUrlJs($this->globalConfig->getUrlJs());
            if (!$this->globalConfig->isSiteActive()) {
                throw new BaseApplicationException();
            }

            $module->handle($this->request, $this->response);
        } catch (RedirectException $exception) {
            $this->response->getHeaders()->sendRedirect($exception->getTargetUrl());
        } catch (NotFoundException $exception) {
            $this->logger->notice('Ошибка 404', [
                'srv' => $_SERVER ?? [],
            ]);

            $this->response->getHeaders()->add('HTTP/1.0 404 Not Found');

            $this->response->getContent()->getHead()->addTitleElement('404 Not Found - страница не найдена на сервере');
            $this->response->getContent()->setH1('Не найдено');
            $this->templateEngine->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->templateEngine->assign('host', _SITE_URL);
            $this->templateEngine->assign('suggestions', []);
            $this->response->getContent()->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/_errors/er404.tpl'));
        } catch (AccessDeniedException $exception) {
            $this->logger->notice('Ошибка 403', [
                'srv' => $_SERVER ?? [],
            ]);

            $this->response->getHeaders()->add('HTTP/1.1 403 Forbidden');

            $this->response->getContent()->getHead()->addTitleElement('403 Forbidden - страница недоступна (запрещено)');
            $this->response->getContent()->setH1('Запрещено');
            $this->templateEngine->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->templateEngine->assign('host', _SITE_URL);
            $this->response->getContent()->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/_errors/er403.tpl'));
        } catch (Throwable $exception) {
            $this->response->getHeaders()->add('Content-Type: text/html; charset=utf-8');
            $this->response->getHeaders()->add('HTTP/1.1 503 Service Temporarily Unavailable');
            $this->response->getHeaders()->add('Status: 503 Service Temporarily Unavailable');
            $this->response->getHeaders()->add('Retry-After: 300');

            $this->response->getContent()->getHead()->addTitleElement('Ошибка 503 - Сервис временно недоступен');
            $this->response->getContent()->setH1('Сервис временно недоступен');
            $this->response->getContent()->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/_errors/er503.tpl'));

            $this->logger->error($exception->getMessage());
        }

        $this->response->getHeaders()->add('X-Powered-By: culttourism');
        $this->response->getHeaders()->add('Content-Type: text/html; charset=utf-8');

        if ($this->response->getLastEditTimestamp() > 0 && !$this->request->isAjax()) {
            $this->response->getHeaders()->add('Last-Modified: ' . $this->response->getLastEditTimeGMT());
            $this->response->getHeaders()->add('Cache-control: public');
            $this->response->getHeaders()->add('Pragma: cache');
            $this->response->getHeaders()->add('Expires: ' . $this->response->getExpiresTimeGMT());
            if ($this->request->getHeader('If-Modified-Since') !== null) {
                $modifiedSince = explode(';', $this->request->getHeader('If-Modified-Since'));
                if (strtotime($modifiedSince[0]) >= $this->response->getLastEditTimestamp()) {
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
            $this->templateEngine->assign('user', $this->user);
            $this->templateEngine->assign('pageContent', $this->response->getContent());

            $this->templateEngine->display(_DIR_TEMPLATES . '/_main/main.html.tpl');
        }

        exit();
    }
}
