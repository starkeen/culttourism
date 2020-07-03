<?php

declare(strict_types=1);

namespace app\api\google_search;

class CachedClient implements HttpClientInterface
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function fetchResponse(Request $request): string
    {
        return $this->client->fetchResponse($request);
    }
}
