<?php

use app\api\yandex_search\Factory;
use app\constant\OgType;
use app\core\SiteRequest;
use app\db\MyDB;
use app\sys\Logger;
use app\sys\SentryLogger;
use app\sys\TemplateEngine;
use Psr\Log\LoggerInterface;

/**
 * Core
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

    public $content = '';
    public $url = '';
    private $_title = ['Культурный туризм'];
    public $title = 'Культурный туризм';
    private $_keywords = ['достопримечательности'];
    public $keywords = 'достопримечательности';
    private $_description = [];
    public $description = '';
    private $metaTagsCustom = [];
    private $metaTagsJSONLD = [
        '@context' => 'http://schema.org',
    ];
    public $canonical;
    public $h1 = '';
    public $counters = '';
    public $isCounters = 0;
    public $isAjax = false;
    public $module_id = _INDEXPAGE_URI;
    public $md_id; //id of module in database
    public $page_id = '';
    private $id_id;

    public $basepath = '';

    public $globalsettings = [
        'default_pagetitle' => '',
        'main_rss' => '',
        'stat_text' => '',
    ];
    public $user = ['userid' => null];
    public $custom_css;
    public $robots_indexing = 'index,follow';
    public $lastedit;
    public $lastedit_timestamp = 0;
    public $expiredate;

    /**
     * @var TemplateEngine
     */
    public $smarty;

    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param MyDB $db
     * @param SiteRequest $request
     */
    protected function __construct(MyDB $db, SiteRequest $request)
    {
        set_exception_handler([$this, 'errorsExceptionsHandler']);
        $this->db = $db;
        $this->siteRequest = $request;
        $this->smarty = new TemplateEngine(); // TODO убрать!

        if (!$this->db->link) {
            $this->module_id = $this->siteRequest->getModuleKey();
            $this->processError(self::HTTP_CODE_503, $this->smarty);
        }

        $this->basepath = _URL_ROOT;

        $this->isAjax = $this->siteRequest->isAjax();

        $this->auth = new Auth($this->db);
        $this->auth->checkSession('web');

        $sp = new MSysProperties($db);
        $this->globalsettings = $sp->getPublic();

        if (_ER_REPORT) {//отладочная конфигурация
            $this->globalsettings['mainfile_css'] = '../sys/static/?type=css&pack=common';
            $this->globalsettings['mainfile_js'] = '../sys/static/?type=js&pack=common';
        }

        if (!empty($this->globalsettings['site_active']) && $this->globalsettings['site_active'] === 'Off') {
            $this->processError(self::HTTP_CODE_503);
        }

        $md = new MModules($this->db);
        $row = $md->getModuleByURI($this->siteRequest->getModuleKey());

        $this->addOGMeta(OgType::SITE_NAME(), $this->globalsettings['default_pagetitle'] ?? '');
        $this->addOGMeta(OgType::LOCALE(), 'ru_RU');
        $this->addOGMeta(OgType::TYPE(), 'website');
        $this->addOGMeta(OgType::URL(), _SITE_URL);
        $this->addOGMeta(OgType::IMAGE(), _SITE_URL . 'img/logo/culttourism-head.jpg');
        $this->addDataLD('image', _SITE_URL . 'img/logo/culttourism-head.jpg');
        if ($row['md_photo_id']) {
            $ph = new MPhotos($this->db);
            $photo = $ph->getItemByPk($row['md_photo_id']);
            $objImage = $this->getAbsoluteURL($photo['ph_src']);
            $this->addOGMeta(OgType::IMAGE(), $objImage);
            $this->addDataLD('image', $objImage);
        }

        if (!empty($row)) {
            if ($row['md_redirect'] !== null) {
                $this->processError(self::HTTP_CODE_301, $row['md_redirect']);
            }
            $this->url = $row['md_url'];
            $this->title = $this->globalsettings['default_pagetitle'] ?? '';
            if ($row['md_title']) {
                $this->addTitle($row['md_title']);
            }
            $this->h1 = $row['md_title'];
            $this->keywords = $this->globalsettings['default_pagekeywords'] ?? '';
            $this->addKeywords($row['md_keywords']);
            $this->description = $this->globalsettings['default_pagedescription'] ?? '';
            $this->addDescription($row['md_description']);

            $this->addOGMeta(OgType::TITLE(), $this->globalsettings['default_pagetitle'] ?? '');
            $this->addOGMeta(OgType::DESCRIPTION(), $this->globalsettings['default_pagedescription'] ?? '');
            $this->addOGMeta(OgType::UPDATED_TIME(), $this->lastedit_timestamp);

            $this->isCounters = $row['md_counters'];
            $this->content = $row['md_pagecontent'];
            $this->md_id = $row['md_id'];
            $this->module_id = $this->siteRequest->getModuleKey();
            $this->page_id = $this->siteRequest->getLevel1();
            $this->id_id = $this->siteRequest->getLevel2();
            $this->custom_css = $row['md_css'];
            $this->robots_indexing = $row['md_robots'];
            $this->lastedit = $row['md_timestamp'];
            $this->lastedit_timestamp = strtotime($row['md_timestamp']);
            $this->expiredate = $row['md_expiredate'];

            if (isset($_SESSION['user'])) {
                $this->user['object'] = $_SESSION['user'];
            }
            if (isset($_SESSION['user_name'])) {
                $this->user['username'] = $_SESSION['user_name'];
                $this->user['userid'] = $_SESSION['user_id'];
            }
        }
    }

    public function display(): void
    {
        if ($this->isAjax) {
            echo $this->content;
        } else {
            $this->smarty->assign('page', $this);
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
     * @param string $text
     */
    public function addTitle(string $text): void
    {
        $this->_title[] = $text;
        krsort($this->_title);
        $this->title = implode(' ' . ($this->globalsettings['title_delimiter'] ?? '') . ' ', $this->_title);
    }

    /**
     *
     * @param string $text
     */
    public function addKeywords(string $text): void
    {
        $this->_keywords[] = $text;
        krsort($this->_keywords);
        $this->keywords = implode(', ', $this->_keywords);
    }

    /**
     *
     * @param string $text
     */
    public function addDescription(string $text): void
    {
        $this->_description[] = trim($text);
        krsort($this->_description);
        $this->description = implode('. ', $this->_description);
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

    private function getSuggestions404Local($req)
    {
        $out = [];
        if (strpos($req, '.html') !== false) {
            $c = new MPageCities($this->db);

            $uri = explode('/', $req);
            array_pop($uri);
            $page = $c->getCityByUrl('/' . trim(implode('/', $uri), '/'));
            if (!empty($page)) {
                $out[] = [
                    'url' => $page['url'] . '/',
                    'title' => $page['pc_title'],
                ];
            }
        }
        return $out;
    }

    /**
     * @param string $req
     *
     * @return array
     */
    private function getSuggestions404Yandex(string $req): array
    {
        $out = [];

        if (strpos($req, '.css') === false
            && strpos($req, '.js') === false
            && strpos($req, '.png') === false
            && strpos($req, '.txt') === false
            && strpos($req, '.xml') === false
        ) {
            $searcher = Factory::build();
            $searcher->setDocumentsOnPage(3);
            $searchString = trim(implode(' ', explode('/', $req))) . ' host:culttourism.ru';
            $result = $searcher->searchPages($searchString, 0);
            if (!$result->isError() && !empty($result->getItems())) {
                foreach ($result->getItems() as $variant) {
                    $out[] = [
                        'url' => $variant->getUrl(),
                        'title' => trim(str_replace('| Культурный туризм', '', $variant->getTitle())),
                    ];
                }
            }
        }

        return $out;
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
            $this->globalsettings['main_rss'] = '';
            $this->basepath = _URL_ROOT;
            $this->mainfile_css = basename($_css_files[0] ?? '/');
            $this->mainfile_js = basename($_js_files[0] ?? '/');
            $this->smarty->assign('page', $this);
            $this->smarty->assign('debug_info', '');
        }
        switch ($errorHttpCode) {
            case self::HTTP_CODE_301: {
                header('HTTP/1.1 301 Moved Permanently');
                header("Location: $errorData");
                exit();
            }
                break;
            case self::HTTP_CODE_403: {
                $errorContext = [
                    'srv' => $_SERVER ?? [],
                ];
                $this->logger->notice('Ошибка 403', $errorContext);

                header('Content-Type: text/html; charset=utf-8');
                header('HTTP/1.1 403 Forbidden');

                $this->title = "$this->title - 403 Forbidden - страница недоступна (запрещено)";
                $this->h1 = 'Запрещено';
                $this->smarty->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                $this->smarty->assign('host', _SITE_URL);
                $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/_errors/er403.sm.html');
            }
                break;
            case self::HTTP_CODE_404: {
                $errorContext = [
                    'srv' => $_SERVER ?? [],
                ];
                $this->logger->notice('Ошибка 404', $errorContext);

                header('Content-Type: text/html; charset=utf-8');
                header('HTTP/1.0 404 Not Found');

                $suggestions = [];
                //$suggestions = array_merge($suggestions, $this->getSuggestions404Local($_SERVER['REQUEST_URI']));
                if (false && empty($suggestions) && strpos($_SERVER['REQUEST_URI'], 'php') === false) {
                    $searchText = trim(str_replace(['_', '/', '.html',], ' ', $_SERVER['REQUEST_URI']));
                    $suggestions = $this->getSuggestions404Yandex($searchText);
                }

                $this->title = "$this->title - 404 Not Found - страница не найдена на сервере";
                $this->h1 = 'Не найдено';
                $this->smarty->assign('requested', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                $this->smarty->assign('host', _SITE_URL);
                $this->smarty->assign('suggestions', $suggestions);
                $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/_errors/er404.sm.html');
            }
                break;
            case self::HTTP_CODE_503: {
                header('Content-Type: text/html; charset=utf-8');
                header('HTTP/1.1 503 Service Temporarily Unavailable');
                header('Status: 503 Service Temporarily Unavailable');
                header('Retry-After: 300');

                $this->title = "$this->title - Ошибка 503 - Сервис временно недоступен";
                $this->h1 = 'Сервис временно недоступен';
                $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/_errors/er503.sm.html');
            }
                break;
        }
        if ($this->module_id === 'api') {
            $this->smarty->display(_DIR_TEMPLATES . '/_main/api.html.sm.html');
        } elseif ($this->isAjax) {
            echo $this->content;
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
