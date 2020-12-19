<?php

declare(strict_types=1);

namespace app\core;

use app\core\page\Content;
use app\core\page\Headers;

class SiteResponse
{
    /**
     * @var Headers
     */
    private $headers;

    /**
     * @var Content
     */
    private $content;

    /**
     * @var int|null
     */
    private $lastEditTimestamp;

    /**
     * @param Headers $headers
     * @param Content $content
     */
    public function __construct(Headers $headers, Content $content)
    {
        $this->headers = $headers;
        $this->content = $content;
    }

    /**
     * @return Headers
     */
    public function getHeaders(): Headers
    {
        return $this->headers;
    }

    /**
     * @return Content
     */
    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * @return int|null
     */
    public function getLastEditTimestamp(): ?int
    {
        return $this->lastEditTimestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setLastEditTimestamp(int $timestamp): void
    {
        $this->lastEditTimestamp = $timestamp;
    }

    /**
     * Установить метку времени последнего редактирования только если она больше текущей
     * @param int $timestamp
     */
    public function setMaxLastEditTimestamp(int $timestamp): void
    {
        if ($timestamp > $this->lastEditTimestamp) {
            $this->lastEditTimestamp = $timestamp;
        }
    }

    /**
     * Установить метку времени последнего редактирования на максимально далёкую дату
     */
    public function setLastEditTimestampToFuture(): void
    {
        $this->lastEditTimestamp = strtotime('+2 month');
    }
}
