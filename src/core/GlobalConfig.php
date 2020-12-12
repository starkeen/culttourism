<?php

declare(strict_types=1);

namespace app\core;

class GlobalConfig
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
     * @return string
     */
    public function getUrlCss(): string
    {
        return $this->urlCss;
    }

    /**
     * @param string $urlCss
     */
    public function setUrlCss(string $urlCss): void
    {
        $this->urlCss = $urlCss;
    }

    /**
     * @return string
     */
    public function getUrlJs(): string
    {
        return $this->urlJs;
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
}
