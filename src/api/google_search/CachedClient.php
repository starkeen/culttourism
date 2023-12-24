<?php

declare(strict_types=1);

namespace app\api\google_search;

use app\api\google_search\exception\SearchException;
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

    /**
     * @param HttpClientInterface $client
     * @param MSearchLog          $cacheModel
     */
    public function __construct(HttpClientInterface $client, MSearchLog $cacheModel)
    {
        $this->client = $client;
        $this->cacheModel = $cacheModel;
    }

    /**
     * @param Request $request
     *
     * @return string
     * @throws SearchException
     */
    public function fetchResponse(Request $request): string
    {
        $requestData = http_build_query($request->getData());
        $response = $this->cacheModel->searchByHash($requestData);

        if ($response === null) {
            $response = $this->client->fetchResponse($request);
            if (!empty($response)) {
                $this->cacheModel->add(
                    [
                        'sl_query' => $request->getQuery(),
                        'sl_request' => $requestData,
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
