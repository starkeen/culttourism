<?php

declare(strict_types=1);

namespace app\crontab;

use app\api\YandexWebmasterAPI;
use app\model\criteria\PointCriteria;
use app\rss\YandexTurboPointsGenerator;

class RssTurboPointsCommand extends CrontabCommand
{
    /**
     * @var YandexTurboPointsGenerator
     */
    private $generator;

    /**
     * @var YandexWebmasterAPI
     */
    private $yandexWebmasterApiClient;

    public function __construct(YandexTurboPointsGenerator $generator, YandexWebmasterAPI $apiClient)
    {
        $this->generator = $generator;
        $this->yandexWebmasterApiClient = $apiClient;
    }

    public function run(string $fileName, string $partialFileName, int $chunkNumber): void
    {
        $baseCriteria = new PointCriteria();
        $baseCriteria->addWhere('LENGTH(pt_description) > 10');
        $baseCriteria->addOrder('pt_rank', PointCriteria::ORDER_DESC);

        $criteria = clone $baseCriteria;
        $criteria->setLimit(1000);
        $xml = $this->generator->getXML($criteria);
        file_put_contents($fileName, $xml->asXML());

        $dailyCriteria = clone $baseCriteria;
        $dailyCriteria->setLimit(10000);
        $dailyCriteria->addWhere('RIGHT(CAST(pt_id AS CHAR), 1) = ' . $chunkNumber);
        $xml = $this->generator->getXML($dailyCriteria);
        file_put_contents($partialFileName, $xml->asXML());

        $this->yandexWebmasterApiClient->uploadRSS($partialFileName);
    }
}
