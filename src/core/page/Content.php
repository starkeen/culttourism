<?php

declare(strict_types=1);

namespace app\core\page;

class Content
{
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
     * @var string|null
     */
    private $customJsModule;

    /**
     * @var string[]
     */
    private $jsResources = [];

    /**
     * @return string
     */
    public function getUrlRss(): string
    {
        return $this->urlRss;
    }

    /**
     * @param string $urlRss
     */
    public function setUrlRss(string $urlRss): void
    {
        $this->urlRss = $urlRss;
    }

    /**
     * @param string $urlCss
     */
    public function setUrlCss(string $urlCss): void
    {
        $this->urlCss = $urlCss;
    }

    /**
     * @param string $urlJs
     */
    public function setUrlJs(string $urlJs): void
    {
        $this->urlJs = $urlJs;
    }

    /**
     * @return string
     */
    public function getUrlJs(): string
    {
        $pack = 'common';
        if ($this->customJsModule !== null) {
            $pack = $this->customJsModule;
            $resourceKey = 'res_js_' . $this->customJsModule;
            $this->urlJs = $this->jsResources[$resourceKey];
        }

        return !_ER_REPORT ? $this->urlJs : '../sys/static/?type=js&pack=' . $pack;
    }

    /**
     * @return string
     */
    public function getUrlCss(): string
    {
        return !_ER_REPORT ? $this->urlCss : '../sys/static/?type=css&pack=common';
    }

    public function setCustomJsModule(string $module): void
    {
        $this->customJsModule = $module;
    }

    /**
     * @deprecated
     * @param array $map
     */
    public function setJsResources(array $map): void
    {
        $this->jsResources = $map;
    }
}
