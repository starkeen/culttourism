<?php

declare(strict_types=1);

namespace app\modules;

use app\core\GlobalConfig;
use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\services\YandexSearch\SearchRequest;
use app\services\YandexSearch\SearchResponse;
use app\services\YandexSearch\ServiceBuilder;
use app\services\YandexSearch\YandexSearchService;
use app\sys\Logger;
use app\sys\TemplateEngine;
use app\utils\JSON;
use MPageCities;
use MPagePoints;
use MSearchCache;

class SearchModule extends Module implements ModuleInterface
{
    private const PAGE_SIZE = 15;
    private const MAX_PASSAGES = 4;

    private const SEARCH_POSTFIX = 'host:culttourism.ru';

    private Logger $logger;

    private YandexSearchService $searchService;

    /**
     * @param MyDB $db
     * @param TemplateEngine $templateEngine
     * @param WebUser $webUser
     * @param GlobalConfig $globalConfig
     * @param Logger $logger
     */
    public function __construct(
        MyDB $db,
        TemplateEngine $templateEngine,
        WebUser $webUser,
        GlobalConfig $globalConfig,
        Logger $logger,
        YandexSearchService $searchService
    ) {
        parent::__construct($db, $templateEngine, $webUser, $globalConfig);
        $this->logger = $logger;
        $this->searchService = $searchService;
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        if ($request->getLevel2() !== null) {
            throw new NotFoundException();
        }
        if ($request->getLevel1() === 'suggest' && isset($_GET['query'])) {
            $this->getSuggests();
        } elseif ($request->getLevel1() === 'suggest-object' && isset($_GET['query'])) {
            $this->getObjectSuggests();
        }
        $response->getContent()->setBody($this->getSearchYandex());
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'search';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }

    /**
     * Саджест регионов
     */
    private function getSuggests(): void
    {
        $out = [
            'query' => '',
            'suggestions' => [],
        ];
        $out['query'] = $this->getCleanedQuery($_GET['query']);
        $pc = new MPageCities($this->db);
        $variants = $pc->getSuggestion($out['query']);

        foreach ($variants as $row) {
            $out['suggestions'][] = [
                'value' => (string) ($row['pc_title']),
                'data' => $row['pc_id'],
                'url' => $row['url'] . '/',
            ];
        }

        JSON::echo($out);
    }

    /**
     * Саджест объектов
     */
    private function getObjectSuggests(): void
    {
        $out = [
            'query' => '',
            'suggestions' => [],
        ];
        $out['query'] = $this->getCleanedQuery($_GET['query']);
        $pt = new MPagePoints($this->db);
        $variants = $pt->getSuggestion($out['query']);

        foreach ($variants as $row) {
            $out['suggestions'][] = [
                'value' => $row['pt_name'],
                'data' => $row['pt_id'],
                'city_id' => $row['pc_id'],
                'city_title' => $row['pc_title'],
                'latitude' => $row['pt_latitude'],
                'longitude' => $row['pt_longitude'],
            ];
        }

        JSON::echo($out);
    }

    /**
     * @return string
     */
    private function getSearchYandex(): string
    {
        $errorText = '';
        $dataResult = [];
        $resultMeta = [
            'query' => '', // запрос
            'page' => 0, // текущая страница
            'pages_all' => 0, // всего доступно страниц
            'resolution' => '', // результаты поиска текстом
            'text_source' => '', // в случае исправлений: исходный текст
            'text_result' => '', // в случае исправлений: исправленный текст
        ];

        if (isset($_GET['q'])) {
            $query = $this->getCleanedQuery($_GET['q']);
            $page = array_key_exists('page', $_GET) ? (int) $_GET['page'] : 0;

            $resultMeta['page'] = $page;
            $resultMeta['query'] = $query;

            $this->log($query);

            $searchKeywords = $query . ' ' . self::SEARCH_POSTFIX;

            $request = new SearchRequest($searchKeywords);
            $request->setNumResults(self::PAGE_SIZE);
            $request->setPage($page);
            $request->setMaxPassages(self::MAX_PASSAGES);

            $result = $this->searchService->search($request);

            if (!$result->isError()) {
                $dataResult = $this->makeResults($result);

                $resultMeta['pages_all'] = $result->getPagesCount();
                $resultMeta['resolution'] = str_replace('нашёл', '', $result->getTotalCountHuman());

                $correctionInfo = $result->getCorrection();
                if ($correctionInfo !== null) {
                    $resultMeta['text_source'] = str_replace(
                        self::SEARCH_POSTFIX ,
                        '',
                        $correctionInfo->getSourceText()
                    );
                    $resultMeta['text_result'] = str_replace(
                        self::SEARCH_POSTFIX,
                        '',
                        $correctionInfo->getResultText()
                    );
                    $resultMeta['query'] = $resultMeta['text_result'];
                }
            } else {
                $errorText = $result->getErrorText();
                $loggerContext = [
                    'query' => $searchKeywords,
                    'page' => $result->getPage(),
                    'request_id' => $result->getRequestID(),
                    'error_code' => $result->getErrorCode(),
                    'error_text' => $result->getErrorText(),
                    'limit' => $request->getNumResults(),
                ];
                $this->logger->warning('Ошибка в поиске', $loggerContext);
            }
        }

        $this->templateEngine->assign('error', $errorText);
        $this->templateEngine->assign('result', $dataResult);
        $this->templateEngine->assign('meta', $resultMeta);

        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/search/search.tpl');
    }

    /**
     * @param SearchResponse $searchResult
     * @return array
     */
    private function makeResults(SearchResponse $searchResult): array
    {
        $results = [];

        foreach ($searchResult->getResults() as $resultItem) {
            $titleItemElements = explode($this->globalConfig->getTitleDelimiter(), $resultItem->getTitle());
            if (count($titleItemElements) > 1) {
                array_pop($titleItemElements);
            }

            $results[] = [
                'title' => str_replace(' , ', ', ', trim(implode(', ', $titleItemElements))),
                'descr' => $resultItem->getSnippet(),
                'url' => $resultItem->getURL(),
            ];
        }

        return $results;
    }

    /**
     * @param string $query
     */
    private function log(string $query): void
    {
        $sc = new MSearchCache($this->db);
        $sc->add(
            [
                'sc_session' => $this->webUser->getHash(),
                'sc_query' => $query,
                'sc_sr_id' => null,
            ]
        );
    }

    /**
     * @param string $raw
     * @return string
     */
    private function getCleanedQuery(string $raw): string
    {
        return htmlentities(trim(strip_tags($raw)), ENT_QUOTES, 'UTF-8');
    }
}
