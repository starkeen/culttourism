<?php

declare(strict_types=1);

namespace app\modules;

use app\api\yandex_search\Factory;
use app\core\GlobalConfig;
use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\sys\Logger;
use app\sys\TemplateEngine;
use MPageCities;
use MPagePoints;
use MSearchCache;

class SearchModule extends Module implements ModuleInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param MyDB $db
     * @param TemplateEngine $templateEngine
     * @param WebUser $webUser
     * @param GlobalConfig $globalConfig
     * @param Logger $logger
     */
    public function __construct(MyDB $db, TemplateEngine $templateEngine, WebUser $webUser, GlobalConfig $globalConfig, Logger $logger)
    {
        parent::__construct($db, $templateEngine, $webUser, $globalConfig);
        $this->logger = $logger;
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
        $out['query'] = htmlentities(cut_trash_string($_GET['query']), ENT_QUOTES, "UTF-8");
        $pc = new MPageCities($this->db);
        $variants = $pc->getSuggestion($out['query']);

        foreach ($variants as $row) {
            $out['suggestions'][] = [
                'value' => (string) ($row['pc_title']),
                'data' => $row['pc_id'],
                'url' => $row['url'] . '/',
            ];
        }

        header('Content-type: application/json');
        echo json_encode($out);
        exit();
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
        $out['query'] = htmlentities(cut_trash_string($_GET['query']), ENT_QUOTES, "UTF-8");
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

        header('Content-type: application/json');
        echo json_encode($out);
        exit();
    }

    private function getSearchYandex()
    {
        if (isset($_GET['q'])) {
            $query = htmlentities(cut_trash_string(strip_tags($_GET['q'])), ENT_QUOTES, "UTF-8");

            $this->log($query);

            $errorText = '';
            $result = [];
            $resultMeta = [
                'query' => $query,
                'page' => array_key_exists('page', $_GET) ? (int) $_GET['page'] : 0,
                'per_page' => 15, // документов на странице
                'pages_all' => 0, // всего доступно страниц
                'total' => 0, // всего найдено документов
                'resolution' => '', // результаты поиска текстом
                'text_source' => '', // в случае исправлений: исходный текст
                'text_result' => '', // в случае исправлений: исправленный текст
            ];

            $searchKeywords = $query . ' host:culttourism.ru';
            $yandexSearcher = Factory::build();
            $yandexSearcher->setDocumentsOnPage($resultMeta['per_page']);

            $searchResult = $yandexSearcher->searchPages($searchKeywords, $resultMeta['page']);

            if (!$searchResult->isError()) {
                foreach ($searchResult->getItems() as $resultItem) {
                    $titleItemElements = explode($this->globalConfig->getTitleDelimiter(), $resultItem->getTitle());
                    if (count($titleItemElements) > 1) {
                        array_pop($titleItemElements);
                    }

                    $result[] = [
                        'title' => str_replace(' , ', ', ', trim(implode(', ', $titleItemElements))),
                        'descr' => $resultItem->getDescription(),
                        'url' => $resultItem->getUrl(),
                    ];
                }
                $resultMeta['pages_all'] = $searchResult->getPagesCount();
                $resultMeta['total'] = $searchResult->getDocumentsCount();
                $resultMeta['resolution'] = str_replace('нашёл', '', $searchResult->getHumanResolution());

                $correctionInfo = $searchResult->getCorrection();
                if ($correctionInfo !== null) {
                    $resultMeta['text_source'] = $correctionInfo->getSourceText();
                    $resultMeta['text_result'] = $correctionInfo->getResultText();
                }
            } else {
                $errorText = $searchResult->getErrorText();
                $loggerContext = [
                    'query' => $searchKeywords,
                    'error_text' => $searchResult->getErrorText(),
                    'limit' => $yandexSearcher->getCurrentLimit(),
                ];
                $this->logger->warning('Ошибка в поиске', $loggerContext);
            }

            $this->templateEngine->assign('search', $query);
            $this->templateEngine->assign('error', $errorText);
            $this->templateEngine->assign('result', $result);
            $this->templateEngine->assign('meta', $resultMeta);
        } else {
            $this->templateEngine->assign('search', '');
            $this->templateEngine->assign('error', '');
            $this->templateEngine->assign('result', '');
            $this->templateEngine->assign('meta', []);
        }
        return $this->templateEngine->fetch(_DIR_TEMPLATES . '/search/search.sm.html');
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
}
