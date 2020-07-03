<?php

declare(strict_types=1);

namespace app\api\google_search;

class GoogleSearch
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function search(string $query): Result
    {
        $request = new Request($query);
        $response = $this->httpClient->fetchResponse($request);

        return new Result($response);
    }
}
