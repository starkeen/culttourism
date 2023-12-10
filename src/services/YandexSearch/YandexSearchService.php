<?php

declare(strict_types=1);

namespace app\services\YandexSearch;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class YandexSearchService
{
    private const YANDEX_SEARCH_URL = 'https://yandex.ru/search/xml';

    private Client $client;

    private string $apiId;
    private string $apiKey;

    public function __construct(Client $client, string $id, string $key)
    {
        $this->apiId = $id;
        $this->apiKey = $key;
        $this->client = $client;
    }
    public function search(SearchRequest $request): SearchResponse
    {
        $result = new SearchResponse($request);

        $response = $this->client->post(
            self::YANDEX_SEARCH_URL,
            [
                RequestOptions::QUERY => [
                    'folderid' => $this->apiId,
                    'l10n' => 'ru',
                    'sortby' => 'rlv',
                    'filter' => 'none',
                ],
                RequestOptions::BODY => $request->getXML(),
                RequestOptions::HEADERS => [
                    'Content-Type' => 'application/xml',
                    'Authorization' => 'Api-Key ' . $this->apiKey,
                ],
            ]
        );

        $xml = simplexml_load_string($response->getBody()->getContents());

        $xmlResponse = $xml->response;
        $result->setRequestID((string)$xmlResponse->reqid);

        if (property_exists($xmlResponse, 'error')) {
            $result->setErrorText((string)$xmlResponse->error);
            return $result;
        }

        if (property_exists($xmlResponse, 'misspell')) {
            $mispell = $xmlResponse->misspell;
            $sourceText = strip_tags($mispell->{'source-text'}->saveXML());
            $correction = new Correction($sourceText, (string)$mispell->text);
            $result->setCorrection($correction);
        }

        $result->setTotalCount((int)($xmlResponse->results->grouping->found[0] ?? 0));
        $result->setTotalCountHuman((string)$xmlResponse->results->grouping->{'found-docs-human'});
        $result->setPageSize((int)$xmlResponse->results->grouping->attributes()['groups-on-page']);
        $result->setPage((int)$xmlResponse->results->grouping->page);

        foreach ($xml->response->results->grouping->group as $group) {
            foreach ($group->doc as $doc) {
                $passage = $doc->passages->passage;
                $result->appendResult(
                    strip_tags($doc->title->saveXML()),
                    (string)$doc->url,
                    $passage !== null ? strip_tags($passage->saveXML()) : ''
                );
            }
        }

        return $result;
    }
}
