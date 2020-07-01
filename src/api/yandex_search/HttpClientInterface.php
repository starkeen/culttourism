<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use Psr\Http\Message\ResponseInterface;

interface HttpClientInterface
{
    public function fetchResponse(QueryDoc $queryDoc): string;
}
