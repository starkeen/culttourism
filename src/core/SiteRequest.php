<?php

declare(strict_types=1);

namespace app\core;

class SiteRequest
{
    /**
     * @var string
     */
    private $requestUri;

    private $parsed = false;

    /**
     * Идентификатор корневого раздела (модуля)
     * @var string|null
     */
    private $moduleId;

    /**
     * Идентификатор страницы внутри раздела - первый уровень
     * @var string|null
     */
    private $level1;

    /**
     * Идентификатор страницы внутри раздела - второй уровень
     * @var string|null
     */
    private $level2;

    /**
     * Идентификатор страницы внутри раздела - третий уровень
     * @var string|null
     */
    private $level3;

    /**
     * @param string $requestUri
     */
    public function __construct(string $requestUri)
    {
        $this->requestUri = $requestUri;
    }

    public function getModuleKey(): string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return urldecode($this->moduleId);
    }

    public function getPageId(): ?string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return $this->level1 !== null ?  urldecode($this->level1) : null;
    }

    public function getLevel1(): ?string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return $this->level1 !== null ?  urldecode($this->level1) : null;
    }

    public function getLevel2(): ?string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return $this->level2 !== null ? urldecode($this->level2) : null;
    }

    public function getLevel3(): ?string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return $this->level3 !== null ?  urldecode($this->level3) : null;
    }

    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function getUrl(): string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }

        $data = [
            $this->getModuleKey(),
            $this->getLevel1(),
            $this->getLevel2(),
            $this->getLevel3(),
        ];
        $urlParts = array_filter($data);

        return '/' . implode('/', $urlParts);
    }

    public function getGET(): array
    {
        return $_GET;
    }

    public function getGETParam(string $name): ?string
    {
        return $_GET[$name] ?? null;
    }

    private function parseRequest(): void
    {
        $requestUri = urldecode($this->requestUri);
        if (strpos($requestUri, '?')) {
            $requestUri = mb_substr($requestUri, 0, strpos($requestUri, '?'), 'utf-8');
        }
        $requestURIArray = explode('/', $requestUri);
        $requestURIParamsList = array_values($requestURIArray);
        $requestURIParamsList = array_filter($requestURIParamsList);

        if (isset($requestURIParamsList[0])) {
            $host_id = $requestURIParamsList[0];
        }
        if (isset($requestURIParamsList[1])) {
            $this->moduleId = $requestURIParamsList[1];
        }
        if (isset($requestURIParamsList[2])) {
            $this->level1 = $requestURIParamsList[2];
        }
        if (isset($requestURIParamsList[3])) {
            $this->level2 = $requestURIParamsList[3];
        }
        if (isset($requestURIParamsList[4])) {
            $this->level3 = $requestURIParamsList[4];
        }

        $this->moduleId = ($this->moduleId !== null && $this->moduleId !== '') ? $this->moduleId : _INDEXPAGE_URI;
        if ($this->moduleId === 'index') {
            $this->moduleId = _INDEXPAGE_URI;
        }

        $this->parsed = true;
    }
}
