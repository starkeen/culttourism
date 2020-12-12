<?php

declare(strict_types=1);

namespace app\core;

use app\db\MyDB;
use MSysProperties;

//    TODO:
//    - sitename
//    - default_pagekeywords
//    - default_pagedescription
//    - default_pagetitle
//    - title_delimiter
//    + main_rss
//    - stat_city
//    - stat_points
//    + mainfile_css
//    + mainfile_js
//    + key_yandexmaps
//    - mail_fromaddr
//    - mail_to
//    - mail_feedback
//    - stat_text
//    - checklinks_shift
//    - index_cnt_blogs
//    - index_cnt_news
//    - res_js_list
//    - res_js_map
//    - res_js_city
//    - res_js_point
//    - res_js_editor
//    - site_active
//    - app_openweather_key
class GlobalConfig
{
    /**
     * @var MSysProperties
     */
    private $propertiesModel;

    /**
     * @var bool
     */
    private $compiled = false;

    /**
     * @var string
     */
    private $urlCss;

    /**
     * @var string
     */
    private $urlJs;

    /**
     * @var string
     */
    private $urlRss;

    /**
     * @var string
     */
    private $yandexMapsKey;

    /**
     * @deprecated
     * @var string[]
     */
    private $jsResources = [];

    public function __construct(MyDB $db)
    {
        $this->propertiesModel = new MSysProperties($db);
    }

    private function compile(): void
    {
        if (!$this->compiled) {
            $globals = $this->propertiesModel->getPublic();

            $this->urlCss = $globals['mainfile_css'];
            $this->urlJs = $globals['mainfile_js'];
            $this->urlRss = $globals['main_rss'];
            $this->yandexMapsKey = $globals['key_yandexmaps'];
            $this->jsResources = [
                'res_js_list' => $globals['res_js_list'],
                'res_js_map' => $globals['res_js_map'],
                'res_js_city' => $globals['res_js_city'],
                'res_js_point' => $globals['res_js_point'],
                'res_js_editor' => $globals['res_js_editor'],
            ];

            $this->compiled = true;
        }
    }

    /**
     * @return string
     */
    public function getUrlCss(): string
    {
        $this->compile();

        return $this->urlCss;
    }

    /**
     * @return string
     */
    public function getUrlJs(): string
    {
        $this->compile();

        return $this->urlJs;
    }

    /**
     * @deprecated
     * @return array
     */
    public function getJsResources(): array
    {
        $this->compile();

        return $this->jsResources;
    }

    /**
     * @return string
     */
    public function getUrlRSS(): string
    {
        $this->compile();

        return $this->urlRss;
    }

    /**
     * @return string
     */
    public function getYandexMapsKey(): string
    {
        $this->compile();

        return $this->yandexMapsKey;
    }
}
