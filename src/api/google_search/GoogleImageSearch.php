<?php

declare(strict_types=1);

namespace app\api\google_search;

class GoogleImageSearch
{
    private $searcher;

    public function __construct(GoogleSearch $searcher)
    {
        $this->searcher = $searcher;
    }

    public function search(string $query): Result
    {
        return $this->searcher->search($query);
    }
}
