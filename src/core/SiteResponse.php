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
     * @param int $lastEditTimestamp
     */
    public function setLastEditTimestamp(int $lastEditTimestamp): void
    {
        $this->lastEditTimestamp = $lastEditTimestamp;
    }
}
