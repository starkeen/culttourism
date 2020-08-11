<?php

declare(strict_types=1);

namespace app\crontab;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use models\MLinks;
use Psr\Log\LoggerInterface;

class CheckUrlsCommand extends CrontabCommand
{
    private const HTTP_REQUEST_OPTIONS = [
        RequestOptions::ALLOW_REDIRECTS => false,
        RequestOptions::CONNECT_TIMEOUT => 2,
        RequestOptions::READ_TIMEOUT => 2,
        RequestOptions::TIMEOUT => 2,
        RequestOptions::FORCE_IP_RESOLVE => 'v4',
        RequestOptions::HEADERS => [
            'User-Agent' => 'culttourism bot/1.0',
        ],
    ];

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

            try {
                $response = $this->httpClient->request('GET', $url, self::HTTP_REQUEST_OPTIONS);

                $statusCodeNew = $response->getStatusCode();
                $contentSize = $response->getBody()->getSize();
            } catch (BadResponseException $exception) {
                $statusCodeNew = $exception->getResponse()->getStatusCode();
                $contentSize = null;
            } catch (ConnectException $exception) {
                $statusCodeNew = 500;
                $contentSize = null;
            } catch (RequestException $exception) {
                continue;
            }

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
