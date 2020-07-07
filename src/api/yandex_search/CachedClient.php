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

    public function fetchResponse(QueryDoc $queryDoc): Result
    {
        $responseText = $this->cacheModel->searchByHash($queryDoc->getBody());
        if ($responseText !== null) {
            $response = new Result($responseText);
            $this->cacheModel->updateHashData($queryDoc->getBody());
        } else {
            $response = $this->httpClient->fetchResponse($queryDoc);
            $this->cacheModel->add(
                [
                    'sl_query' => $queryDoc->getKeywords(),
                    'sl_request' => $queryDoc->getBody(),
                    'sl_answer' => $response->getString(),
                    'sl_error_code' => $response->getErrorCode() ?? 0,
                    'sl_error_text' => $response->getErrorText(),
                ]
            );
        }

        return $response;
    }

    public function fetchLimitResponse(): string
    {
        return $this->httpClient->fetchLimitResponse();
    }
}
