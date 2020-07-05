<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use MSearchLog;

class CachedClient implements HttpClientInterface
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var MSearchLog
     */
    private $cacheModel;

    public function __construct(HttpClientInterface $client, MSearchLog $cacheModel)
    {
        $this->httpClient = $client;
        $this->cacheModel = $cacheModel;
    }

    public function fetchResponse(QueryDoc $queryDoc): string
    {
        $response = $this->cacheModel->searchByHash($queryDoc->getBody());
        if ($response === null) {
            $response = $this->httpClient->fetchResponse($queryDoc);
            $this->cacheModel->add(
                [
                    'sl_query' => $queryDoc->getKeywords(),
                    'sl_request' => $queryDoc->getBody(),
                    'sl_answer' => $response,
                ]
            );
        } else {
            $this->cacheModel->updateHashData($queryDoc->getBody());
        }

        return $response;
    }

    public function fetchLimitResponse(): string
    {
        return $this->httpClient->fetchLimitResponse();
    }
}
