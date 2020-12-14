<?php

use app\constant\OgType;
use app\core\GlobalConfig;
use app\core\page\Content;
use app\core\page\Headers;
use app\core\SiteRequest;
use app\db\MyDB;
use app\sys\TemplateEngine;
use Psr\Log\LoggerInterface;

/**
 * Core - ядро
 */
abstract class Core
{
    public const HTTP_CODE_301 = 301;
    public const HTTP_CODE_403 = 403;
    public const HTTP_CODE_404 = 404;
    public const HTTP_CODE_503 = 503;

    private static $hInstances = []; // хэш экземпляров классов

    /**
     * @var MyDB
     */
    protected $db;

    /**
     * @var SiteRequest
     */
    protected $siteRequest;

    /**
     * @var TemplateEngine
     */
    public $smarty;

    /**
     * @var Auth
     */
    public $auth;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var GlobalConfig
     */
    protected $globalConfig;

    /**
     * @var Content
     */
    public $pageContent;

    /**
     * @var Headers
     */
    public $pageHeaders;

    public $url = '';
    private $metaTagsCustom = [];
    private $metaTagsJSONLD = [
        '@context' => 'http://schema.org',
    ];
    public $module_id = _INDEXPAGE_URI;
    public $md_id; //id of module in database
    public $page_id = '';
    private $id_id;

    public $basepath = '';

    public $user = ['userid' => null];
    public $custom_css;
    public $robots_indexing = 'index,follow';
    public $lastedit;
    public $lastedit_timestamp = 0;
    public $expiredate;

    /**
     * @param MyDB $db
     * @param SiteRequest $request
     */
    protected function __construct(MyDB $db, SiteRequest $request)
    {
        set_exception_handler([$this, 'errorsExceptionsHandler']);
        $this->db = $db;
        $this->siteRequest = $request;
        $this->basepath = _URL_ROOT;
        $this->globalConfig = new GlobalConfig($this->db);
    }

    /**
     * Инициализация базовых элементов всех страниц
     */
    private function init(): void
    {
        $this->auth->checkSession('web');

        $this->pageContent->getHead()->setTitleDelimiter($this->globalConfig->getTitleDelimiter());
        $this->pageContent->getHead()->addTitleElement('Культурный туризм');

        $this->pageContent->setJsResources($this->globalConfig->getJsResources());
        $this->pageContent->setUrlCss($this->globalConfig->getUrlCss());
        $this->pageContent->setUrlJs($this->globalConfig->getUrlJs());
        $this->pageContent->setUrlRss($this->globalConfig->getUrlRSS());

        $this->key_yandexmaps = $this->globalConfig->getYandexMapsKey();

        if (!$this->globalConfig->isSiteActive()) {
            $this->processError(self::HTTP_CODE_503);
        }

        $md = new MModules($this->db);
        $moduleData = $md->getModuleByURI($this->siteRequest->getModuleKey());

        $this->addOGMeta(OgType::TITLE(), $this->pageContent->getHead()->getTitle());
        $this->addOGMeta(OgType::DESCRIPTION(), $this->pageContent->getHead()->getDescription());
        $this->addOGMeta(OgType::SITE_NAME(), $this->globalConfig->getDefaultPageTitle());
        $this->addOGMeta(OgType::LOCALE(), 'ru_RU');
        $this->addOGMeta(OgType::TYPE(), 'website');
        $this->addOGMeta(OgType::URL(), rtrim(_SITE_URL, '/') . $_SERVER['REQUEST_URI']);
        $this->addOGMeta(OgType::IMAGE(), _SITE_URL . 'img/logo/culttourism-head.jpg');
        $this->addDataLD('image', _SITE_URL . 'img/logo/culttourism-head.jpg');
        if ($moduleData['md_photo_id']) {
            $ph = new MPhotos($this->db);
            $photo = $ph->getItemByPk($moduleData['md_photo_id']);
            $objImage = $this->getAbsoluteURL($photo['ph_src']);
            $this->addOGMeta(OgType::IMAGE(), $objImage);
            $this->addDataLD('image', $objImage);
        }

        if (!empty($moduleData)) {
            if ($moduleData['md_redirect'] !== null) {
                $this->processError(self::HTTP_CODE_301, $moduleData['md_redirect']);
            }
            $this->url = $moduleData['md_url'];
            $this->pageContent->getHead()->addTitleElement($this->globalConfig->getDefaultPageTitle());
            if ($moduleData['md_title']) {
                $this->pageContent->getHead()->addTitleElement($moduleData['md_title']);
            }
            $this->pageContent->setH1($moduleData['md_title']);
            $this->pageContent->getHead()->addKeyword($this->globalConfig->getDefaultPageKeywords());
            $this->pageContent->getHead()->addKeyword($moduleData['md_keywords']);
            $this->pageContent->getHead()->addDescription($this->globalConfig->getDefaultPageDescription());
            $this->pageContent->getHead()->addDescription($moduleData['md_description']);

            $this->addOGMeta(OgType::TITLE(), $this->globalConfig->getDefaultPageTitle());
            $this->addOGMeta(OgType::DESCRIPTION(), $this->globalConfig->getDefaultPageDescription());
            $this->addOGMeta(OgType::UPDATED_TIME(), $this->lastedit_timestamp);

            if ($moduleData['md_pagecontent'] !== null) {
                $this->pageContent->setBody($moduleData['md_pagecontent']);
            }
            $this->md_id = $moduleData['md_id'];
            $this->module_id = $this->siteRequest->getModuleKey();
            $this->page_id = $this->siteRequest->getLevel1();
            $this->id_id = $this->siteRequest->getLevel2();
            $this->custom_css = $moduleData['md_css'];
            $this->robots_indexing = $moduleData['md_robots'];
            $this->lastedit = $moduleData['md_timestamp'];
            $this->lastedit_timestamp = strtotime($moduleData['md_timestamp']);
            $this->expiredate = $moduleData['md_expiredate'];

            if (isset($_SESSION['user'])) {
                $this->user['object'] = $_SESSION['user'];
            }
            if (isset($_SESSION['user_name'])) {
                $this->user['username'] = $_SESSION['user_name'];
                $this->user['userid'] = $_SESSION['user_id'];
            }
        }
    }

    /**
     * Определение типа страницы внутри модуля и формирование контента
     */
    abstract protected function compileContent(): void;

    public function display(): void
    {
        $this->init();
        $this->compileContent();

        if ($this->siteRequest->isAjax()) {
            echo $this->pageContent->getBody();
        } else {
            $this->smarty->assign('page', $this);
            $this->smarty->assign('pageContent', $this->pageContent);
            $this->smarty->caching = false;
            if (_ER_REPORT || isset($_GET['debug'])) {
                $this->smarty->assign('debug_info', $this->db->getDebugInfoText());
            } else {
                $this->smarty->assign('debug_info', '');
            }
            if ($this->module_id === 'api') {
                $this->smarty->display(_DIR_TEMPLATES . '/_main/api.html.sm.html');
            } else {
                $this->smarty->display(_DIR_TEMPLATES . '/_main/main.html.sm.html');
            }
        }
    }

    /**
     * Добавляет в разметку мета-теги OpenGraph
     *
     * @param OgType $ogType
     * @param string $value
     */
    public function addOGMeta(OgType $ogType, string $value): void
    {
        $this->addCustomMeta('og:' . $ogType->getValue(), $value);
    }

    /**
     * Добавление произвольного мета-тега
     *
     * @param string $key
     * @param string|null $value
     */
    public function addCustomMeta(string $key, ?string $value): void
    {
        $val = trim(html_entity_decode(strip_tags($value)));
        if (empty($val)) {
            return;
        }

        $this->metaTagsCustom[$key] = $val;
    }

    /**
     * Получение набора кастомных мета-тегов
     *
     * @return array
     */
    public function getCustomMetas(): array
    {
        ksort($this->metaTagsCustom);
        return $this->metaTagsCustom;
    }

    /**
     * Добавляет данные в набор JSON+LD
     *
     * @param string $key
     * @param string $value
     */
    public function addDataLD($key, $value): void
    {
        if (is_scalar($value)) {
            $val = trim(html_entity_decode(strip_tags($value)));
        } elseif (is_array($value)) {
            $val = array_filter($value);
        }
        if (empty($val)) {
            return;
        }
        $this->metaTagsJSONLD[$key] = $val;
    }

    /**
     * Данные для блока ld+json
     * @return array
     */
    public function getJSONLD(): array
    {
        ksort($this->metaTagsJSONLD);
        return !empty($this->metaTagsJSONLD['@type']) ? $this->metaTagsJSONLD : [];
    }

    public function errorsExceptionsHandler($e): void
    {
        $msg = "Error: " . $e->getMessage() . "\n"
            . 'file: ' . $e->getFile() . ':' . $e->getLine() . "\n"
            . 'URI: ' . ($_SERVER['REQUEST_URI'] ?? 'undefined') . "\n"
            . "\n__________________________\n\n\n"
            . 'trace: ' . print_r($e->getTrace(), true) . "\n";

        mail('starkeen@gmail.com', 'Error on ' . _URL_ROOT, $msg);
        if (ob_get_length()) {
            ob_end_clean();
        }
        $this->processError(self::HTTP_CODE_503);
    }

    /**
     * @param int $errorHttpCode
     * @param mixed|null $errorData
     */
    public function processError(int $errorHttpCode = self::HTTP_CODE_404, $errorData = null): void
    {
        if ($errorHttpCode !== self::HTTP_CODE_301) {
            $_css_files = glob(_DIR_ROOT . '/css/ct-common-*.min.css');
            $_js_files = glob(_DIR_ROOT . '/js/ct-common-*.min.js');
            $this->pageContent->setUrlRss('');
            $this->basepath = _URL_ROOT;
            $this->pageContent->setUrlCss(basename($_css_files[0] ?? '/'));
            $this->pageContent->setUrlJs(basename($_js_files[0] ?? '/'));
            $this->smarty->assign('page', $this);
            $this->smarty->assign('pageContent', $this->pageContent);
            $this->smarty->assign('debug_info', '');
        }
        switch ($errorHttpCode) {
            case self::HTTP_CODE_301: {
                $this->pageHeaders->add('HTTP/1.1 301 Moved Permanently');
                $this->pageHeaders->add('Location: ' . $errorData);
                $this->pageHeaders->flush();
                exit();
            }
                break;
            case self::HTTP_CODE_403: {
                $errorContext = [
                    'srv' => $_SERVER ?? [],
                ];
                $this->logger->notice('Ошибка 403', $errorContext);

                $this->pageHeaders->add('Content-Type: text/html; charset=utf-8');
                $this->pageHeaders->add('HTTP/1.1 403 Forbidden');

                $this->pageContent->getHead()->addTitleElement('403 Forbidden - страница недоступна (запрещено)');
                $this->pageContent->setH1('Запрещено');
                $this->smarty->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                $this->smarty->assign('host', _SITE_URL);
                $this->pageContent->setBody($this->smarty->fetch(_DIR_TEMPLATES . '/_errors/er403.sm.html'));
            }
                break;
            case self::HTTP_CODE_404: {
                $errorContext = [
                    'srv' => $_SERVER ?? [],
                ];
                $this->logger->notice('Ошибка 404', $errorContext);

                $this->pageHeaders->add('Content-Type: text/html; charset=utf-8');
                $this->pageHeaders->add('HTTP/1.0 404 Not Found');

                $suggestions = [];
                $this->pageContent->getHead()->addTitleElement('404 Not Found - страница не найдена на сервере');
                $this->pageContent->setH1('Не найдено');
                $this->smarty->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                $this->smarty->assign('host', _SITE_URL);
                $this->smarty->assign('suggestions', $suggestions);
                $this->pageContent->setBody($this->smarty->fetch(_DIR_TEMPLATES . '/_errors/er404.sm.html'));
            }
                break;
            case self::HTTP_CODE_503: {
                $this->pageHeaders->add('Content-Type: text/html; charset=utf-8');
                $this->pageHeaders->add('Content-Type: text/html; charset=utf-8');
                $this->pageHeaders->add('HTTP/1.1 503 Service Temporarily Unavailable');
                $this->pageHeaders->add('Status: 503 Service Temporarily Unavailable');
                $this->pageHeaders->add('Retry-After: 300');

                $this->pageContent->getHead()->addTitleElement('Ошибка 503 - Сервис временно недоступен');
                $this->pageContent->setH1('Сервис временно недоступен');
                $this->pageContent->setBody($this->smarty->fetch(_DIR_TEMPLATES . '/_errors/er503.sm.html'));
            }
                break;
        }
        if ($this->module_id === 'api') {
            $this->smarty->display(_DIR_TEMPLATES . '/_main/api.html.sm.html');
        } elseif ($this->siteRequest->isAjax()) {
            echo $this->pageContent->getBody();
        } else {
            $this->smarty->display(_DIR_TEMPLATES . '/_main/main.html.sm.html');
        }
        exit();
    }

    protected function checkRedirect(string $url): void
    {
        $redirectModel = new MRedirects($this->db);
        $redirects = $redirectModel->getActive();
        foreach ($redirects as $redirect) {
            $redirectUrl = preg_filter($redirect['rd_from'], $redirect['rd_to'], $url);
            if ($redirectUrl !== null) {
                $this->processError(self::HTTP_CODE_301, $redirectUrl);
            }
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getAbsoluteURL(string $path): string
    {
        return strpos($path, '/') === 0 ? rtrim(
                _SITE_URL,
                '/'
            ) . $path : $path;
    }

    /**
     * запрещаем клонировать экземпляр класса
     * @throws RuntimeException
     */
    protected function __clone()
    {
        throw new RuntimeException('Can not clone singleton');
    }

    /**
     * @param string $sClassname
     * @param MyDB $db
     * @param SiteRequest $request
     *
     * @return self
     */
    protected static function getInstanceOf(string $sClassname, MyDB $db, SiteRequest $request): self
    {
        if (!isset(self::$hInstances[$sClassname])) {
            self::$hInstances[$sClassname] = new $sClassname($db, $request); // создаем экземпляр
        }

        return self::$hInstances[$sClassname];
    }
}
