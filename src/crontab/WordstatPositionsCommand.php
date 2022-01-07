<?php

declare(strict_types=1);

namespace app\crontab;

use app\api\yandex_search\Factory;
use app\sys\Logger;
use MWordstat;

class WordstatPositionsCommand extends AbstractCrontabCommand
{
    private const PORTION_SIZE = 1;
    private const LIMIT_DOMAINS_PER_ANSWER = 90;

    private MWordstat $wordstatModel;
    private Logger $logger;

    public function __construct(MWordstat $ws, Logger $logger)
    {
        $this->wordstatModel = $ws;
        $this->logger = $logger;
    }

    public function run(): void
    {
        $cities = $this->wordstatModel->getPortionPosition(self::PORTION_SIZE);

        $searcher = Factory::build();
        $searcher->setDocumentsOnPage(self::LIMIT_DOMAINS_PER_ANSWER);

        foreach ($cities as $city) {
            $domains = [
                0 => null,
            ];
            $query = sprintf('%s  достопримечательности', $city['ws_city_title']);
            $result = $searcher->searchPages($query);
            if (!$result->isError()) {
                foreach ($result->getItems() as $site) {
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
                        'limit' => $searcher->getCurrentLimit(),
                        'error_code' => $result->getErrorCode(),
                        'error_text' => $result->getErrorText(),
                    ]
                );
            }
        }
    }
}
