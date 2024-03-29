<?php

declare(strict_types=1);

namespace app\core;

use app\core\exception\CoreException;
use RuntimeException;

class SiteRequest
{
    public const INDEX_PAGE_URI = 'index.html';

    /**
     * @var string
     */
    private $requestUri;

    /**
     * @var bool
     */
    private $parsed = false;

    /**
     * Домен сайта в запросе
     *
     * @var string|null
     */
    private $hostId;

    /**
     * Идентификатор корневого раздела (модуля)
     *
     * @var string|null
     */
    private $moduleId;

    /**
     * Идентификатор страницы внутри раздела - первый уровень
     *
     * @var string|null
     */
    private $level1;

    /**
     * Идентификатор страницы внутри раздела - второй уровень
     *
     * @var string|null
     */
    private $level2;

    /**
     * Идентификатор страницы внутри раздела - третий уровень
     *
     * @var string|null
     */
    private $level3;

    /**
     * @var array
     */
    private $headers;

    /**
     * @param string $requestUri
     */
    public function __construct(string $requestUri)
    {
        $this->requestUri = $requestUri;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return urldecode($this->hostId);
    }

    /**
     * @return string
     */
    public function getModuleKey(): string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return urldecode($this->moduleId);
    }

    /**
     * @return string|null
     */
    public function getLevel1(): ?string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return $this->level1 !== null ? urldecode($this->level1) : null;
    }

    /**
     * @return string|null
     */
    public function getLevel2(): ?string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return $this->level2 !== null ? urldecode($this->level2) : null;
    }

    /**
     * @return string|null
     */
    public function getLevel3(): ?string
    {
        if (!$this->parsed) {
            $this->parseRequest();
        }
        return $this->level3 !== null ? urldecode($this->level3) : null;
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @return string
     */
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

    /**
     * @return array
     */
    public function getGET(): array
    {
        return $_GET;
    }

    /**
     * @param  string $name
     * @return string|null
     */
    public function getGETParam(string $name): ?string
    {
        return $_GET[$name] ?? null;
    }

    /**
     * @return string
     */
    public function getCurrentURL(): string
    {
        return 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * @param  string $name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        if ($this->headers === null) {
            $allHeaders = getallheaders();
            if ($allHeaders === false) {
                throw new CoreException('Не удалось получить заголовки запроса');
            }
            $this->headers = [];
            foreach ($allHeaders as $key => $value) {
                $key = strtolower($key);
                $this->headers[$key] = $value;
            }
        }

        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Сделан ли запрос через HTTPS
     *
     * @return bool
     */
    public function isSSL(): bool
    {
        return isset($_SERVER['HTTP_X_HTTPS']) && $_SERVER['HTTP_X_HTTPS'] !== '';
    }

    /**
     * Определяет POST-запрос
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return !empty($_POST);
    }

    /**
     * Возвращает параметр POST-запроса по имени
     *
     * @param  string $name
     * @return int|string|bool|null
     */
    public function getPostParameter(string $name)
    {
        return $_POST[$name] ?? null;
    }

    /**
     * @return string|null
     */
    public function getReferer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Разбор запроса на составляющие
     */
    private function parseRequest(): void
    {
        $decodedRequestUri = urldecode($this->requestUri);
        if (strpos($decodedRequestUri, '?')) {
            $decodedRequestUri = mb_substr($decodedRequestUri, 0, strpos($decodedRequestUri, '?'), 'utf-8');
        }
        $requestURIArray = explode('/', $decodedRequestUri);
        $requestURIParamsList = array_values($requestURIArray);
        $requestURIParamsList = array_filter($requestURIParamsList);

        if (isset($requestURIParamsList[0])) {
            $this->hostId = $requestURIParamsList[0];
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

        $this->moduleId = ($this->moduleId !== null && $this->moduleId !== '') ? $this->moduleId : self::INDEX_PAGE_URI;
        if ($this->moduleId === 'index') {
            $this->moduleId = self::INDEX_PAGE_URI;
        }

        $this->parsed = true;
    }
}
