<?php

declare(strict_types=1);

namespace app\api\google_search;

class Request
{
    private $query;

    public function __construct(string $query)
    {
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
