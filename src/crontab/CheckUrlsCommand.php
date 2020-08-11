<?php

declare(strict_types=1);

namespace app\crontab;

use app\db\MyDB;
use GuzzleHttp\ClientInterface;
use models\MLinks;
use Psr\Log\LoggerInterface;

class CheckUrlsCommand extends CrontabCommand
{
    /**
     * @var MLinks
     */
    private $linksModel;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(MLinks $linksModel, ClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->linksModel = $linksModel;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function run(): void
    {
        $this->linksModel->makeCache();

        $portion = $this->linksModel->getCheckPortion(20);
        foreach ($portion as $urlData) {
            $id = (int) $urlData['id'];
            $url = $urlData['url'];
            $statusCodeOld = $urlData['status'];

            $response = $this->httpClient->request('GET', $url);

            $statusCodeNew = $response->getStatusCode();
            $contentSize = $response->getBody()->getSize();
            if ($statusCodeOld !== $statusCodeNew) {
                $context = [
                    'url' => $url,
                    'old' => $statusCodeOld,
                    'new' => $statusCodeNew,
                ];
                $this->logger->info('Изменился статус ответа URL', $context);
            }

            $this->linksModel->updateStatus($id, $statusCodeNew, $contentSize);
        }
    }
}
