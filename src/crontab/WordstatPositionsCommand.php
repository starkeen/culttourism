<?php

declare(strict_types=1);

namespace app\crontab;

use app\services\YandexSearch\SearchRequest;
use app\services\YandexSearch\YandexSearchService;
use app\sys\Logger;
use MWordstat;

class WordstatPositionsCommand extends AbstractCrontabCommand
{
    private const PORTION_SIZE = 1;
    private const LIMIT_DOMAINS_PER_ANSWER = 90;

    private MWordstat $wordstatModel;
    private YandexSearchService $searchService;
    private Logger $logger;

    public function __construct(MWordstat $ws, YandexSearchService $searchService, Logger $logger)
    {
        $this->wordstatModel = $ws;
        $this->searchService = $searchService;
        $this->logger = $logger;
    }

    public function run(): void
    {
        $cities = $this->wordstatModel->getPortionPosition(self::PORTION_SIZE);

        foreach ($cities as $city) {
            $domains = [
                0 => null,
            ];
            $query = sprintf('%s  достопримечательности', $city['ws_city_title']);

            $request = new SearchRequest($query);
            $request->setNumResults(self::LIMIT_DOMAINS_PER_ANSWER);

            $result =  $this->searchService->search($request);

            if (!$result->isError()) {
                foreach ($result->getResults() as $site) {
                    $domains[] = $site->getDomain();
                }

                $founded = array_search(GLOBAL_URL_ROOT, $domains, true);
                if ($founded === false) {
                    $position = 0;
                } else {
                    $position = $founded;
                }

                $this->wordstatModel->updateByPk(
                    $city['ws_id'],
                    [
                        'ws_position' => $position,
                        'ws_position_date' => $this->wordstatModel->now(),
                    ]
                );
            } elseif ($result->getErrorCode() !== 15) {
                $this->logger->warning(
                    'Ошибка в скрипте wordstat',
                    [
                        'query' => $query,
                        'limit' => $request->getNumResults(),
                        'error_code' => $result->getErrorCode(),
                        'error_text' => $result->getErrorText(),
                    ]
                );
            }
        }
    }
}
