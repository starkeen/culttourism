<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class YandexSearchAPI
{
    private const SERVICE_URL = 'https://yandex.ru/search/xml';

    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $key;

    private $maxPagesCount = 5;

    public function __construct(ClientInterface $guzzleClient, LoggerInterface $logger, string $user, string $key)
    {
        $this->guzzleClient = $guzzleClient;
        $this->logger = $logger;
        $this->user = $user;
        $this->key = $key;
    }

    public function searchPages(string $keywords, int $page = 0): Result
    {
        $doc = $this->buildQuery($keywords, $page);

        $response = $this->getData($doc);

        return new Result($response);
    }

    private function getData(QueryDoc $doc): string
    {
        $urlParams = [
            'user' => $this->user,
            'key' => $this->key,
            'l10n' => 'ru',
            'sortby' => 'rlv',
            'filter' => 'strict',
        ];

        $url = self::SERVICE_URL . '?' . http_build_query($urlParams);

        $requestParams = [
            RequestOptions::HEADERS => [
                'Content-Type' => 'text/xml;charset=UTF-8',
                'Content-length'=> $doc->getLength(),
            ],
            RequestOptions::BODY => $doc->getString(),
        ];

        $response = $this->guzzleClient->request('POST', $url, $requestParams);

        return $response->getBody()->getContents();
    }

    /**
     * Построение XML-запроса
     *
     * @param string $keywords - поисковая строка
     * @param int $page
     *
     * @return QueryDoc
     */
    private function buildQuery(string $keywords, int $page): QueryDoc
    {
        $query = html_entity_decode($keywords, ENT_QUOTES, 'utf-8');

        $doc = new QueryDoc();
        $doc->setKeywords($query);
        $doc->setPage($page);
        $doc->setMaxPagesCount($this->maxPagesCount);

        return $doc;
    }
}
