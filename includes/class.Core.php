<?php

/**
 * Description of classCore
 *
 * @author Andrey_Pns
 */
abstract class Core {

    private static $hInstances = array(); // хэш экземпляров классов
    public $content = '';
    public $url = '';
    private $_title = array('Культурный туризм');
    public $title = 'Культурный туризм';
    private $_keywords = array('достопримечательности');
    public $keywords = 'достопримечательности';
    private $_description = array();
    public $description = '';
    public $canonical = null;
    public $h1 = '';
    public $counters = '';
    public $isIndex = 0;
    public $isCounters = 0;
    public $isAjax = false;
    public $module_id = _INDEXPAGE_URI;
    public $md_id = null; //id of module in database
    public $page_id = '';
    private $id_id = null;
    protected $db = null;
    public $basepath = '';
    public $globalsettings = array();
    public $user = array('userid' => null);
    public $custom_css = null;
    public $robots_indexing = 'index,follow';
    public $lastedit = null;
    public $lastedit_timestamp = 0;
    public $expiredate = null;
    public $smarty = null;
    protected $auth = null;

    protected static function getInstanceOf($sClassname, $db, $mod) {
        if (!isset(self::$hInstances[$sClassname])) {
            self::$hInstances[$sClassname] = new $sClassname($db, $mod); // создаем экземпляр
        }
        return self::$hInstances[$sClassname];
    }

    protected function __construct($db, $mod) {
        $this->db = $db;
        $this->smarty = new mySmarty();
        if (!$this->db->link) {
            $this->module_id = $mod;
            return $this->getError('503', $this->smarty);
        }
        $mod_id = $mod;
        $page_id = null;
        $id = null;

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->isAjax = true;
        }

        $this->auth = new Auth($this->db);
        $this->auth->checkSession('web');

        $dbm = $this->db->getTableName('modules');
        $sp = new MSysProperties($db);
        $this->globalsettings = $sp->getPublic();

        if ($this->globalsettings['site_active'] == 'Off') {
            $this->getError('503');
        }

        $db->sql = "SELECT dbm.*,
                        DATE_FORMAT(dbm.md_lastedit,'%a, %d %b %Y %H:%i:%s GMT') AS md_timestamp,
                        DATE_FORMAT(date_add(md_lastedit,interval " . _CACHE_DAYS . " day),'%a, %d %b %Y %H:%i:%s GMT') md_expiredate
                    FROM $dbm AS dbm
                    WHERE dbm.md_active = '1'";
        //$db->showSQL();
        $res = $db->exec();
        $this->basepath = _URL_ROOT;
        while ($row = mysql_fetch_assoc($res)) {
            if ($row['md_url'] == $mod_id) {
                if ($row['md_redirect'] !== null) {
                    $this->getError('301', $row['md_redirect']);
                }
                $this->url = $row['md_url'];
                $this->title = $this->globalsettings['default_pagetitle'];
                if ($row['md_title']) {
                    $this->addTitle($row['md_title']);
                }
                $this->h1 = $row['md_title'];
                $this->keywords = $this->globalsettings['default_pagekeywords'];
                $this->addKeywords($row['md_keywords']);
                $this->description = $this->globalsettings['default_pagedescription'];
                $this->addDescription($row['md_description']);
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
                $this->getCounters();

                if (isset($_SESSION['user'])) {
                    $this->user['object'] = $_SESSION['user'];
                }
                if (isset($_SESSION['user_name'])) {
                    $this->user['username'] = $_SESSION['user_name'];
                    $this->user['userid'] = $_SESSION['user_id'];
                }
                break;
            }
        }
        if (!$this->url) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function getCounters() {
        if ($this->isCounters != 0) {
            $dbc = $this->db->getTableName('counters');
            $this->db->sql = "SELECT cnt_text FROM $dbc WHERE cnt_active = '1' ORDER BY cnt_sort";
            $this->db->exec();
            while ($row = $this->db->fetch()) {
                $this->counters .= $row['cnt_text'];
            }
        }
    }

    public function getError($err_code = '404', $err_data = null) {

        if ($err_code != '301') {
            $css_dir = scandir(_DIR_ROOT . '/css', true);
            $_css_files = array();
            foreach ($css_dir as $css_file) {
                if (substr($css_file, 0, 6) == 'common') {
                    $_css_files[] = $css_file;
                }
            }
            $this->globalsettings['mainfile_css'] = $_css_files[0];
            $this->globalsettings['mainfile_js'] = '';
            $this->globalsettings['key_yandexmaps'] = '';
            $this->globalsettings['key_google'] = '';
            $this->globalsettings['key_yandex'] = '';
            $this->globalsettings['key_bing'] = '';
            $this->globalsettings['key_yahoo'] = '';
            $this->globalsettings['main_rss'] = '';
            $this->basepath = _URL_ROOT;
            $this->mainfile_css = $this->globalsettings['mainfile_css'];
            $smarty = $err_data;
            if (!$smarty) {
                global $smarty;
            }
            $smarty->assign('page', $this);
            $smarty->assign('debug_info', '');
        }
        switch ($err_code) {
            case '301': {
                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: ../../../$err_data");
                    exit();
                }
                break;
            case '403': {
                    Logging::write($err_code, $err_data);
                    header('Content-Type: text/html; charset=utf-8');
                    header('HTTP/1.1 403 Forbidden');
                    $this->title = "$this->title - 403 Forbidden - страница недоступна (запрещено)";
                    $this->h1 = 'Запрещено';
                    $this->content = file_get_contents(_DIR_TEMPLATES . '/_errors/er403.sm.html');
                    $this->content = str_replace('<!--#echo var="REQUEST_URI" -->', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $this->content);
                    $this->content = str_replace('<!--#echo var="HTTP_SIGNATURE" -->', $_SERVER['SERVER_SIGNATURE'], $this->content);
                    $this->content = str_replace('<!--#echo var="HTTP_HOST" -->', _URL_ROOT, $this->content);
                }
                break;
            case '404': {
                    Logging::write($err_code, $err_data);
                    header('Content-Type: text/html; charset=utf-8');
                    header("HTTP/1.0 404 Not Found");

                    $this->title = "$this->title - 404 Not Found - страница не найдена на сервере";
                    $this->h1 = 'Не найдено';
                    $this->content = file_get_contents(_DIR_TEMPLATES . '/_errors/er404.sm.html');
                    $this->content = str_replace('<!--#echo var="REQUEST_URI" -->', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $this->content);
                    $this->content = str_replace('<!--#echo var="HTTP_SIGNATURE" -->', $_SERVER['SERVER_SIGNATURE'], $this->content);
                    $this->content = str_replace('<!--#echo var="HTTP_HOST" -->', _URL_ROOT, $this->content);
                }
                break;
            case '503': {
                    header('Content-Type: text/html; charset=utf-8');
                    header('HTTP/1.1 503 Service Temporarily Unavailable');
                    header('Status: 503 Service Temporarily Unavailable');
                    header('Retry-After: 300');

                    $this->title = "$this->title - Ошибка 503 - Сервис временно недоступен";
                    $this->h1 = 'Сервис временно недоступен';
                    $this->content = file_get_contents(_DIR_TEMPLATES . '/_errors/er503.sm.html');
                }
                break;
        }
        if ($this->module_id == 'api') {
            $smarty->display(_DIR_TEMPLATES . '/_main/api.html.sm.html');
        } elseif ($this->module_id == 'ajax') {
            $smarty->display(_DIR_TEMPLATES . '/_main/empty.sm.html');
        } else {
            $smarty->display(_DIR_TEMPLATES . '/_main/main.html.sm.html');
        }
        exit();
    }

    public function addTitle($text) {
        $this->_title[] = $text;
        krsort($this->_title);
        $this->title = implode(' ' . $this->globalsettings['title_delimiter'] . ' ', $this->_title);
    }

    public function addKeywords($text) {
        $this->_keywords[] = $text;
        krsort($this->_keywords);
        $this->keywords = implode(', ', $this->_keywords);
    }

    public function addDescription($text) {
        $this->_description[] = trim($text);
        krsort($this->_description);
        $this->description = implode('. ', $this->_description);
    }

    /* запрещаем клонировать экземпляр класса */

    protected function __clone() {
        throw new Exception('Cannot clone singleton');
    }

}

?>
