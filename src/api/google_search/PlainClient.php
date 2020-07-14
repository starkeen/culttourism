<?php

declare(strict_types=1);

namespace app\api\google_search;

use app\api\google_search\exception\QuotaExceededException;
use app\api\google_search\exception\SearchException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

class PlainClient implements HttpClientInterface
{
    private const SERVICE_URL = 'https://www.googleapis.com/customsearch/v1';

    private $client;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $cx;

    /**
     * @param ClientInterface $client
     * @param string $key
     * @param string $cx
     */
    public function __construct(ClientInterface $client, string $key, string $cx)
    {
        $this->client = $client;
        $this->key = $key;
        $this->cx = $cx;
    }

    /**
     * @param Request $request
     *
     * @return string
     * @throws QuotaExceededException
     * @throws SearchException
     */
    public function fetchResponse(Request $request): string
    {
        $defaultUrlParams = [
            'key' => $this->key,
            'cx' => $this->cx,
        ];

        $urlParams = array_merge($defaultUrlParams, $request->getData());

        $url = self::SERVICE_URL . '?' . http_build_query($urlParams);

        try {
            $response = $this->client->request('GET', $url);
        } catch (ClientException $exception) {
            $responseCode = $exception->getResponse()->getStatusCode();
            $responseContents = $exception->getResponse()->getBody()->getContents();
            $responseData = json_decode($responseContents, true);

            if ($responseCode === 429) {
                throw new QuotaExceededException($responseData['error']['message']);
            }
            throw new SearchException($responseData['error']['message'], $responseCode, $exception);
        }

        return $response->getBody()->getContents();
    }
}
