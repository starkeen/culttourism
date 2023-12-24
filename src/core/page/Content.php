<?php

declare(strict_types=1);

namespace app\core\page;

class Content
{
    private Head $head;

    private string $body = '';

    private ?array $json = null;

    private ?string $h1 = null;

    private string $urlCss;

    private string $urlJs;

    private string $urlRss;

    private ?string $yandexMapsKey = null;

    private ?string $customJsModule = null;

    private array $jsResources = [];

    /**
     * @param Head $head
     */
    public function __construct(Head $head)
    {
        $this->head = $head;
    }

    /**
     * @return Head
     */
    public function getHead(): Head
    {
        return $this->head;
    }

    /**
     * @return string|null
     */
    public function getH1(): ?string
    {
        return $this->h1;
    }

    /**
     * @param string $h1
     */
    public function setH1(string $h1): void
    {
        $this->h1 = $h1;
    }

    /**
     * @param string $bodyContent
     */
    public function setBody(string $bodyContent): void
    {
        $this->body = $bodyContent;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array|null
     */
    public function getJson(): ?array
    {
        return $this->json;
    }

    /**
     * @return string|null
     */
    public function getJsonString(): ?string
    {
        $result = null;

        if ($this->json !== null) {
            $result = json_encode($this->json, JSON_THROW_ON_ERROR);
        }

        return $result;
    }

    /**
     * @param array|null $json
     */
    public function setJson(array $json): void
    {
        $this->json = $json;
    }

    /**
     * @param string $html
     */
    public function setJsonHtml(string $html): void
    {
        $this->json = ['html' => $html];
    }

    /**
     * @return string|null
     */
    public function getUrlRss(): ?string
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
        if ($this->urlJs === null) {
            $jsFiles = glob(GLOBAL_DIR_ROOT . '/js/ct-common-*.min.js');
            if (!empty($jsFiles)) {
                $this->urlJs = basename($jsFiles[0]);
            }
        }

        return !GLOBAL_ERROR_REPORTING ? '/js/' . $this->urlJs : '/sys/static/?type=js&pack=' . $pack;
    }

    /**
     * @return string
     */
    public function getUrlCss(): string
    {
        if ($this->urlCss === null) {
            $cssFiles = glob(GLOBAL_DIR_ROOT . '/css/ct-common-*.min.css');
            if (!empty($cssFiles)) {
                $this->urlCss = basename($cssFiles[0]);
            }
        }

        return !GLOBAL_ERROR_REPORTING ? '/css/' . $this->urlCss : '/sys/static/?type=css&pack=common';
    }

    public function setCustomJsModule(string $module): void
    {
        $this->customJsModule = $module;
    }

    /**
     * @deprecated
     * @param      array $map
     */
    public function setJsResources(array $map): void
    {
        $this->jsResources = $map;
    }

    public function getYandexMapsKey(): string
    {
        return $this->yandexMapsKey;
    }

    public function setYandexMapsKey(string $yandexMapsKey): void
    {
        $this->yandexMapsKey = $yandexMapsKey;
    }
}
