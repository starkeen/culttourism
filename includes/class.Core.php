<?php

use app\sys\Logging;
use app\sys\TemplateEngine;

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
    public $canonical = null;
    public $h1 = '';
    public $counters = '';
    public $isIndex = 0;
    public $isCounters = 0;
    public $isAjax = false;
    public $module_id = _INDEXPAGE_URI;
    public $md_id; //id of module in database
    public $page_id = '';
    private $id_id;
    protected $db;
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
    public $smarty;
    protected $auth;

    protected function __construct($db, $mod)
    {
        set_exception_handler([$this, 'errorsExceptionsHandler']);
        $this->db = $db;
        $this->smarty = new TemplateEngine();
        if (!$this->db->link) {
            $this->module_id = $mod;
            $this->processError(self::HTTP_CODE_503, $this->smarty);
        }
        $mod_id = $mod;
        $page_id = null;
        $id = null;
        $this->basepath = _URL_ROOT;

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(
                $_SERVER['HTTP_X_REQUESTED_WITH']
            ) === 'xmlhttprequest') {
            $this->isAjax = true;
        }

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
        $row = $md->getModuleByURI($mod_id);

        $this->addOGMeta('site_name', $this->globalsettings['default_pagetitle'] ?? '');
        $this->addOGMeta('locale', 'ru_RU');
        $this->addOGMeta('type', 'website');
        $this->addOGMeta('url', _SITE_URL);
        $this->addOGMeta('image', _SITE_URL . 'img/logo/culttourism-head.jpg');
        $this->addDataLD('image', _SITE_URL . 'img/logo/culttourism-head.jpg');
        if ($row['md_photo_id']) {
            $ph = new MPhotos($this->db);
            $photo = $ph->getItemByPk($row['md_photo_id']);
            $objImage = $this->getAbsoluteURL($photo['ph_src']);
            $this->addOGMeta('image', $objImage);
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

            $this->addOGMeta('title', $this->globalsettings['default_pagetitle'] ?? '');
            $this->addOGMeta('description', $this->globalsettings['default_pagedescription'] ?? '');
            $this->addOGMeta('updated_time', $this->lastedit_timestamp);

            $this->isCounters = $row['md_counters'];
            $this->content = $row['md_pagecontent'];
            $this->md_id = $row['md_id'];
            $this->module_id = $mod_id;
            $this->page_id = $page_id;
            $this->id_id = $id;
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

    /**
     * @param string $text
     */
    public function addTitle($text): void
    {
        $this->_title[] = $text;
        krsort($this->_title);
        $this->title = implode(' ' . ($this->globalsettings['title_delimiter'] ?? '') . ' ', $this->_title);
    }

    /**
     *
     * @param string $text
     */
    public function addKeywords($text): void
    {
        $this->_keywords[] = $text;
        krsort($this->_keywords);
        $this->keywords = implode(', ', $this->_keywords);
    }

    /**
     *
     * @param string $text
     */
    public function addDescription($text): void
    {
        $this->_description[] = trim($text);
        krsort($this->_description);
        $this->description = implode('. ', $this->_description);
    }

    /**
     * Добавляет в разметку мета-теги OpenGraph
     *
     * @param string $key
     * @param string $value
     */
    public function addOGMeta(string $key, string $value): void
    {
        $allowTags = ['app_id', 'title', 'type', 'locale', 'url', 'image', 'site_name', 'description', 'updated_time'];
        if (!in_array($key, $allowTags, true)) {
            return;
        }
        $this->addCustomMeta('og:' . $key, $value);
    }

    /**
     * Добавление произвольного мета-тега
     *
     * @param string $key
     * @param string $value
     */
    public function addCustomMeta($key, $value): void
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
     *
     * @return boolean
     */
    public function addDataLD($key, $value)
    {
        if (is_scalar($value)) {
            $val = trim(html_entity_decode(strip_tags($value)));
        } elseif (is_array($value)) {
            $val = array_filter($value);
        }
        if (empty($val)) {
            return false;
        }
        $this->metaTagsJSONLD[$key] = $val;
    }

    /**
     * Данные для блока ld+json
     * @return string
     */
    public function getJSONLD()
    {
        ksort($this->metaTagsJSONLD);
        return !empty($this->metaTagsJSONLD['@type']) ? $this->metaTagsJSONLD : null;
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
        if (strpos($req, '.css') === false && strpos($req, '.js') === false && strpos($req, '.png') === false && strpos(
                $req,
                '.txt'
            ) === false && strpos($req, '.xml') === false) {
            $ys = new YandexSearcher();
            $ys->enableLogging($this->db);
            $searchString = trim(implode(' ', explode('/', $req)));
            $variants = $ys->search("$searchString host:culttourism.ru");
            if (!empty($variants['results'])) {
                $i = 0;
                foreach ($variants['results'] as $variant) {
                    $out[] = [
                        'url' => $variant['url'],
                        'title' => trim(str_replace('| Культурный туризм', '', $variant['title'])),
                    ];
                    if ($i++ === 3) {
                        break;
                    }
                }
            }
        }
        return $out;
    }

    public function errorsExceptionsHandler($e)
    {
        $msg = "Error: " . $e->getMessage() . "\n"
            . 'file: ' . $e->getFile() . ':' . $e->getLine() . "\n"
            . 'URI: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'undefined') . "\n"
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
                Logging::writeError($errorHttpCode);

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
                Logging::writeError($errorHttpCode);

                header('Content-Type: text/html; charset=utf-8');
                header('HTTP/1.0 404 Not Found');

                $suggestions = [];
                //$suggestions = $this->getSuggestions404Local($_SERVER['REQUEST_URI']);
                if (false && empty($suggestions) && !strstr($_SERVER['REQUEST_URI'], 'php')) {
                    $search_text = trim(str_replace(['_', '/', '.html',], ' ', $_SERVER['REQUEST_URI']));
                    $suggestions = $this->getSuggestions404Yandex($search_text);
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
        return substr($path, 0, 1) === '/' ? rtrim(
                _SITE_URL,
                '/'
            ) . $path : $path;
    }

    /**
     * запрещаем клонировать экземпляр класса
     * @throws Exception
     */
    protected function __clone()
    {
        throw new Exception('Can not clone singleton');
    }

    /**
     * @param string $sClassname
     * @param $db
     * @param $mod
     *
     * @return self
     */
    protected static function getInstanceOf($sClassname, $db, $mod)
    {
        if (!isset(self::$hInstances[$sClassname])) {
            self::$hInstances[$sClassname] = new $sClassname($db, $mod); // создаем экземпляр
        }
        return self::$hInstances[$sClassname];
    }
}
