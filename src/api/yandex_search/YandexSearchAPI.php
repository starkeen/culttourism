<?php

declare(strict_types=1);

namespace app\api\yandex_search;

class YandexSearchAPI
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var int
     */
    private $maxDocumentsOnPage = 10;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setDocumentsOnPage(int $count): void
    {
        $this->maxDocumentsOnPage = $count;
    }

    public function searchPages(string $keywords, int $page = 0): Result
    {
        $doc = $this->buildQuery($keywords, $page);

        $response = $this->httpClient->fetchResponse($doc);

        return new Result($response);
    }

    /**
     * Лимит запросов на час
     * @return int
     */
    public function getCurrentLimit(): int
    {
        $response = $this->httpClient->fetchLimitResponse();
        $result = new LimitResult($response);

        return $result->getCurrentLimit();
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
        $doc->setMaxDocumentsPerPage($this->maxDocumentsOnPage);

        return $doc;
    }
}
