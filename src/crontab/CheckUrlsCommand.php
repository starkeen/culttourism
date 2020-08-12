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
use Throwable;

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
            $context = null;
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
                $context = [
                    'url' => $url,
                    'old' => $statusCodeOld,
                    'page' => $urlData['pt_name'],
                    'city' => $urlData['pc_title_unique'],
                    'exception_message' => $exception->getMessage(),
                    'exception_response' => $exception->getResponse(),
                ];
                $this->logger->warning('Сетевая ошибка в запросе', $context);
                continue;
            } catch (Throwable $exception) {
                $context = [
                    'url' => $url,
                    'old' => $statusCodeOld,
                    'page' => $urlData['pt_name'],
                    'city' => $urlData['pc_title_unique'],
                    'exception_message' => $exception->getMessage(),
                ];
                $this->logger->warning('Системная ошибка в сетевом запросе', $context);
                continue;
            }

            if ($statusCodeOld !== $statusCodeNew) {
                $context = [
                    'url' => $url,
                    'page' => $urlData['pt_name'],
                    'city' => $urlData['pc_title_unique'],
                    'old' => $statusCodeOld,
                    'new' => $statusCodeNew,
                ];
                $this->logger->info('Изменился статус ответа URL', $context);
            }

            $this->linksModel->updateStatus($id, $statusCodeNew, $contentSize);
        }
    }
}
