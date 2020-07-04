<?php

declare(strict_types=1);

namespace app\api\google_search;

use MSearchLog;

class CachedClient implements HttpClientInterface
{
    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var MSearchLog
     */
    private $cacheModel;

    public function __construct(HttpClientInterface $client, MSearchLog $cacheModel)
    {
        $this->client = $client;
        $this->cacheModel = $cacheModel;
    }

    public function fetchResponse(Request $request): string
    {
        $requestData = http_build_query($request->getData());
        $response = $this->cacheModel->searchByHash($requestData);

        if ($response === null) {
            $this->cacheModel->add(
                [
                    'sl_query' => $request->getQuery(),
                    'sl_request' => $requestData,
                ]
            );
            $response = $this->client->fetchResponse($request);
            if (!empty($response)) {
                $this->cacheModel->setAnswer(
                    [
                        'sl_answer' => $response,
                    ]
                );
            }
        } else {
            $this->cacheModel->updateHashData($requestData);
        }

        return $response;
    }
}
