<?php

declare(strict_types=1);

namespace app\api\yandex_search;

interface HttpClientInterface
{
    public function fetchResponse(QueryDoc $queryDoc): Result;
    public function fetchLimitResponse(): string;
}
