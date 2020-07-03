<?php

declare(strict_types=1);

namespace app\api\google_search;

use GuzzleHttp\ClientInterface;

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

    public function __construct(ClientInterface $client, string $key, string $cx)
    {
        $this->client = $client;
        $this->key = $key;
        $this->cx = $cx;
    }

    public function fetchResponse(Request $request): string
    {
        $urlParams = [
            'key' => $this->key,
            'cx' => $this->cx,
            'q' => $request->getQuery(),
        ];

        $url = self::SERVICE_URL . '?' . http_build_query($urlParams);

        $response = $this->client->request('GET', $url);

        return $response->getBody()->getContents();
    }
}
