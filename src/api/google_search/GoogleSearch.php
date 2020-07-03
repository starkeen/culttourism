<?php

declare(strict_types=1);

namespace app\api\google_search;

class GoogleSearch
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var int
     */
    private $limit = 10;

    /**
     * @var array
     */
    private $options = [];

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function search(string $query): Result
    {
        $request = new Request($query);
        $request->setLimit($this->limit);
        $request->setOptions($this->options);

        $response = $this->httpClient->fetchResponse($request);

        return new Result($response);
    }

    public function setDocumentsOnPage(int $count): void
    {
        $this->limit = $count;
    }

    /**
     * @param string[] $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function setOption(string $name, string $value): void
    {
        $this->options[$name] = $value;
    }
}
