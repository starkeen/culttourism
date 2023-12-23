<?php

declare(strict_types=1);

namespace app\core;

use app\db\MyDB;
use MSysProperties;

class GlobalConfig
{
    private MSysProperties $propertiesModel;

    private ?Properties $properties = null;

    public function __construct(MyDB $db)
    {
        $this->propertiesModel = new MSysProperties($db);
    }

    /**
     * @return string
     */
    public function getUrlCss(): string
    {
        return $this->getProperties()->mainfile_css;
    }

    /**
     * @return string
     */
    public function getUrlJs(): string
    {
        return $this->getProperties()->mainfile_js;
    }

    /**
     * @deprecated
     * @return array
     */
    public function getJsResources(): array
    {
        return [
            'res_js_list' => $this->getProperties()->res_js_list,
            'res_js_map' => $this->getProperties()->res_js_map,
            'res_js_city' => $this->getProperties()->res_js_city,
            'res_js_point' => $this->getProperties()->res_js_point,
            'res_js_editor' => $this->getProperties()->res_js_editor,
        ];
    }

    /**
     * @return string
     */
    public function getUrlRSS(): string
    {
        return $this->getProperties()->main_rss;
    }

    /**
     * @return string
     */
    public function getYandexMapsKey(): string
    {
        return $this->getProperties()->key_yandexmaps;
    }

    /**
     * @return bool
     */
    public function isSiteActive(): bool
    {
        return $this->getProperties()->site_active !== 'Off';
    }

    /**
     * @return string
     */
    public function getTitleDelimiter(): string
    {
        return ' ' . $this->getProperties()->title_delimiter . ' ';
    }

    /**
     * @return string
     */
    public function getOpenWeatherAPIKey(): string
    {
        return $this->getProperties()->app_openweather_key;
    }

    /**
     * @return string
     */
    public function getMailFeedback(): string
    {
        return $this->getProperties()->mail_feedback;
    }

    /**
     * @return string
     */
    public function getIndexStatText(): string
    {
        return $this->getProperties()->stat_text;
    }

    /**
     * @return int
     */
    public function getIndexStatCountBlog(): int
    {
        return (int) $this->getProperties()->index_cnt_blogs;
    }

    /**
     * @return int
     */
    public function getIndexStatCountNews(): int
    {
        return (int) $this->getProperties()->index_cnt_news;
    }

    /**
     * @return string
     */
    public function getDefaultPageTitle(): string
    {
        return $this->getProperties()->default_pagetitle;
    }

    /**
     * @return string
     */
    public function getDefaultPageKeywords(): string
    {
        return $this->getProperties()->default_pagekeywords;
    }

    /**
     * @return string
     */
    public function getDefaultPageDescription(): string
    {
        return $this->getProperties()->default_pagedescription;
    }

    /**
     * @return Properties
     */
    private function getProperties(): Properties
    {
        if ($this->properties === null) {

            $this->properties = new Properties();
            $globals = $this->propertiesModel->getPublic();
            foreach ($globals as $key => $value) {
                $this->properties->{$key} = $value;
            }
        }
        return $this->properties;
    }
}
