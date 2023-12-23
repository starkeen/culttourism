<?php

declare(strict_types=1);

namespace app\core\application;

use app\constant\OgType;
use app\constant\WrongUrls;
use app\core\CookieStorage;
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
use app\modules\AboutModule;
use app\modules\AjaxModule;
use app\modules\ApiModule;
use app\modules\BlogModule;
use app\modules\CityModule;
use app\modules\DefaultModule;
use app\modules\FeedbackModule;
use app\modules\ListModule;
use app\modules\MainPageModule;
use app\modules\MapModule;
use app\modules\PictureModule;
use app\modules\PointsModule;
use app\modules\RedirectsModule;
use app\modules\SearchModule;
use app\modules\SignModule;
use app\modules\SysModule;
use app\utils\Urls;
use Auth;
use GuzzleHttp\Client;
use Throwable;
use YandexSearchAPI\YandexSearchService;

class WebApplication extends Application
{
    private ?SiteRequest $request = null;

    private ?SiteResponse $response = null;

    private ?WebUser $user = null;

    private ?GlobalConfig $globalConfig = null;

    private ?SessionStorage $sessionStorage = null;

    public function init(): void
    {
        $this->getSessionStorage()->start();
        parent::init();

        // временно отключил сохранение авторизационных токенов в БД
        // $this->getWebUser()->getAuth()->checkSession('web');

        $defaultPageImage = GLOBAL_SITE_URL . 'img/logo/culttourism-head.jpg';
        $this->getSiteResponse()->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $defaultPageImage);
        $this->getSiteResponse()->getContent()->getHead()->addMainMicroData('image', $defaultPageImage);
    }

    public function run(): void
    {
        $this->init();

        try {
            // редиректим на https
            if (!GLOBAL_ERROR_REPORTING && !$this->getSiteRequest()->isSSL()) {
                $url = $this->getSiteRequest()->getCurrentURL();
                throw new RedirectException($url);
            }

            $module = $this->getModuleFetcher()->getModule($this->getSiteRequest());

            $this->getSiteResponse()->getContent()->getHead()->setTitleDelimiter($this->getGlobalConfig()->getTitleDelimiter());
            $this->getSiteResponse()->getContent()->setUrlRss($this->getGlobalConfig()->getUrlRSS());
            $this->getSiteResponse()->getContent()->setYandexMapsKey($this->getGlobalConfig()->getYandexMapsKey());
            $this->getSiteResponse()->getContent()->setJsResources($this->getGlobalConfig()->getJsResources());
            $this->getSiteResponse()->getContent()->setUrlCss($this->getGlobalConfig()->getUrlCss());
            $this->getSiteResponse()->getContent()->setUrlJs($this->getGlobalConfig()->getUrlJs());
            if (!$this->getGlobalConfig()->isSiteActive()) {
                throw new BaseApplicationException();
            }

            $module->handle($this->getSiteRequest(), $this->getSiteResponse());
        } catch (RedirectException $exception) {
            $this->getSiteResponse()->getHeaders()->sendRedirect($exception->getTargetUrl(), true);
        } catch (NotFoundException $exception) {
            $this->logError404($exception);

            $this->getSiteResponse()->getHeaders()->add('HTTP/1.0 404 Not Found');

            $this->getSiteResponse()->getContent()->getHead()->addTitleElement(
                '404 Not Found - страница не найдена на сервере'
            );
            $this->getSiteResponse()->getContent()->setH1('Не найдено');
            $this->getTemplateEngine()->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->getTemplateEngine()->assign('host', GLOBAL_SITE_URL);
            $this->getTemplateEngine()->assign('suggestions', []);
            $this->getSiteResponse()->getContent()->setBody($this->getTemplateEngine()->fetch(GLOBAL_DIR_TEMPLATES . '/_errors/er404.tpl'));
        } catch (AccessDeniedException $exception) {
            $this->getLogger()->notice('Ошибка 403', [
                'srv' => $_SERVER ?? [],
                'trace' => $exception->getTrace(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $this->getSiteResponse()->getHeaders()->add('HTTP/1.1 403 Forbidden');

            $this->getSiteResponse()->getContent()->getHead()->addTitleElement('403 Forbidden - страница недоступна (запрещено)');
            $this->getSiteResponse()->getContent()->setH1('Запрещено');
            $this->getTemplateEngine()->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $this->getTemplateEngine()->assign('host', GLOBAL_SITE_URL);
            $this->getSiteResponse()->getContent()->setBody($this->getTemplateEngine()->fetch(GLOBAL_DIR_TEMPLATES . '/_errors/er403.tpl'));
        } catch (Throwable $exception) {
            $this->getSiteResponse()->getHeaders()->add('Content-Type: text/html; charset=utf-8');
            $this->getSiteResponse()->getHeaders()->add('HTTP/1.1 503 Service Temporarily Unavailable');
            $this->getSiteResponse()->getHeaders()->add('Status: 503 Service Temporarily Unavailable');
            $this->getSiteResponse()->getHeaders()->add('Retry-After: 300');

            $this->getSiteResponse()->getContent()->getHead()->addTitleElement('Ошибка 503 - Сервис временно недоступен');
            $this->getSiteResponse()->getContent()->setH1('Сервис временно недоступен');
            $this->getSiteResponse()->getContent()->setBody($this->getTemplateEngine()->fetch(GLOBAL_DIR_TEMPLATES . '/_errors/er503.tpl'));

            $this->getLogger()->sendSentryException($exception);
        }

        $this->getSiteResponse()->getHeaders()->add('X-Powered-By: culttourism');


        if ($this->getSiteRequest()->isAjax() && $this->getSiteResponse()->getContent()->getJson() !== null) {
            $this->getSiteResponse()->getHeaders()->add('Content-Type: application/json');
        } else {
            $this->getSiteResponse()->getHeaders()->add('Content-Type: text/html; charset=utf-8');
        }

        if ($this->getSiteResponse()->getLastEditTimestamp() > 0 && !$this->getSiteRequest()->isAjax()) {
            $this->getSiteResponse()->getHeaders()->add('Last-Modified: ' . $this->getSiteResponse()->getLastEditTimeGMT());
            $this->getSiteResponse()->getHeaders()->add('Cache-control: public');
            $this->getSiteResponse()->getHeaders()->add('Pragma: cache');
            $this->getSiteResponse()->getHeaders()->add('Expires: ' . $this->getSiteResponse()->getExpiresTimeGMT());
            if ($this->getSiteRequest()->getHeader('If-Modified-Since') !== null) {
                $modifiedSince = explode(';', $this->getSiteRequest()->getHeader('If-Modified-Since'));
                if (strtotime($modifiedSince[0]) >= $this->getSiteResponse()->getLastEditTimestamp()) {
                    $this->getSiteResponse()->getHeaders()->add('HTTP/1.1 304 Not Modified');
                    $this->getSiteResponse()->getHeaders()->flush();
                    exit();
                }
            }
        } else {
            $this->getSiteResponse()->getHeaders()->add('Cache-Control: no-store, no-cache, must-revalidate');
            $this->getSiteResponse()->getHeaders()->add('Expires: ' . date('r'));
        }

        $this->getSiteResponse()->getHeaders()->flush();

        if ($this->getSiteRequest()->isAjax()) {
            echo $this->getSiteResponse()->getContent()->getJsonString();
        } else {
            $this->processMetaTags();

            $this->getTemplateEngine()->displayPage(
                '_main/main.html.tpl',
                [
                    'user' => $this->getWebUser(),
                    'pageContent' => $this->getSiteResponse()->getContent(),
                ]
            );
        }
    }

    private function processMetaTags(): void
    {
        $siteName = $this->globalConfig->getDefaultPageTitle();
        $title = $this->getSiteResponse()->getContent()->getHead()->getTitle();
        $description = $this->getSiteResponse()->getContent()->getHead()->getDescription();
        $canonicalUrl = $this->getSiteResponse()->getContent()->getHead()->getCanonicalUrl();
        if ($canonicalUrl === null) {
            $canonicalUrl = Urls::getAbsoluteURL($_SERVER['REQUEST_URI']);
        }

        $this->getSiteResponse()->getContent()->getHead()->addOGMeta(OgType::SITE_NAME(), $siteName);
        $this->getSiteResponse()->getContent()->getHead()->addOGMeta(OgType::TITLE(), $title);
        $this->getSiteResponse()->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $description);
        $this->getSiteResponse()->getContent()->getHead()->addOGMeta(OgType::URL(), $canonicalUrl);

        $this->getSiteResponse()->getContent()->getHead()->addOGMeta(OgType::LOCALE(), 'ru_RU');
        if ($this->getSiteResponse()->getContent()->getHead()->getOGMeta(OgType::TYPE()) === null) {
            $this->getSiteResponse()->getContent()->getHead()->addOGMeta(OgType::TYPE(), 'website');
        }
    }

    private function logError404(Throwable $exception): void
    {
        $url = $this->getSiteRequest()->getUrl();
        if (in_array($url, WrongUrls::LIST, true)) {
            return;
        }

        $this->getLogger()->notice('Ошибка 404', [
            'srv' => $_SERVER ?? [],
            'trace' => $exception->getTrace(),
        ]);
    }

    private function getSessionStorage(): SessionStorage
    {
        if ($this->sessionStorage === null) {
            $this->sessionStorage = new SessionStorage();
        }
        return $this->sessionStorage;
    }

    public function setSessionStorage(SessionStorage $storage): void
    {
        $this->sessionStorage = $storage;
    }

    private function getSiteRequest(): SiteRequest
    {
        if ($this->request === null) {
            $this->request = new SiteRequest($_SERVER['REQUEST_URI']);
        }
        return $this->request;
    }

    public function setSiteRequest(SiteRequest $request): void
    {
        $this->request = $request;
    }

    private function getSiteResponse(): SiteResponse
    {
        if ($this->response === null) {
            $this->response = new SiteResponse(new Headers(), new Content(new Head()));
        }
        return $this->response;
    }

    public function setSiteResponse(SiteResponse $response): void
    {
        $this->response = $response;
    }

    private function getGlobalConfig(): GlobalConfig
    {
        if ($this->globalConfig === null) {
            $this->globalConfig = new GlobalConfig($this->getDb());
        }
        return $this->globalConfig;
    }

    public function setGlobalConfig(GlobalConfig $globalConfig): void
    {
        $this->globalConfig = $globalConfig;
    }

    private function getWebUser(): WebUser
    {
        if ($this->user === null) {
            $this->user = new WebUser(new Auth($this->getDb(), new CookieStorage()), $this->getSessionStorage());
        }
        return $this->user;
    }

    public function setWebUser(WebUser $webUser): void
    {
        $this->user = $webUser;
    }

    private function getSearchService(): YandexSearchService
    {
        $service = new YandexSearchService(new Client(), $this->getLogger());
        $service->setApiId(YANDEX_SEARCH_ID);
        $service->setApiKey(YANDEX_SEARCH_KEY);

        return $service;
    }

    private function getModuleFetcher(): ModuleFetcher
    {
        $modules =  [
            new RedirectsModule($this->getDb()),
            new MainPageModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
            new AjaxModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser()),
            new MapModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
            new ListModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
            new PointsModule($this->getDb(),$this->getWebUser()),
            new CityModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
            new PictureModule($this->getDb(), $this->getWebUser()),
            new SearchModule(
                $this->getDb(),
                $this->getTemplateEngine(),
                $this->getWebUser(),
                $this->getGlobalConfig(),
                $this->getLogger(),
                $this->getSearchService()
            ),
            new BlogModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
            new FeedbackModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
            new AboutModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
            new SignModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
            new SysModule(),
            new ApiModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
            new DefaultModule($this->getDb(), $this->getTemplateEngine(), $this->getWebUser(), $this->getGlobalConfig()),
        ];

        return new ModuleFetcher($modules);
    }
}
