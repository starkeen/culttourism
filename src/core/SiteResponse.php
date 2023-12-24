<?php

declare(strict_types=1);

namespace app\core;

use app\core\page\Content;
use app\core\page\Headers;

class SiteResponse
{
    private const EXPIRES_HEADER_SHIFT = 60 * 60 * 24 * 7;

    private Headers $headers;

    private Content $content;

    private ?int $lastEditTimestamp = null;

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
     * @return string|null
     */
    public function getLastEditTimeGMT(): ?string
    {
        return $this->lastEditTimestamp !== null ? gmdate('D, d M Y H:i:s', $this->lastEditTimestamp) . ' GMT' : null;
    }

    /**
     * @return string|null
     */
    public function getExpiresTimeGMT(): ?string
    {
        return $this->lastEditTimestamp !== null ? gmdate('D, d M Y H:i:s', $this->lastEditTimestamp + self::EXPIRES_HEADER_SHIFT) . ' GMT' : null;
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
     *
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
        $this->lastEditTimestamp = time() + self::EXPIRES_HEADER_SHIFT;
    }
}
