<?php

declare(strict_types=1);

namespace app\crontab;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RedirectMiddleware;
use GuzzleHttp\RequestOptions;
use models\MLinks;
use Psr\Log\LoggerInterface;
use Throwable;

class CheckUrlsCommand extends CrontabCommand
{
    private const HTTP_REQUEST_OPTIONS = [
        RequestOptions::ALLOW_REDIRECTS => [
            'track_redirects' => true,
        ],
        RequestOptions::CONNECT_TIMEOUT => 2,
        RequestOptions::READ_TIMEOUT => 2,
        RequestOptions::TIMEOUT => 2,
        RequestOptions::FORCE_IP_RESOLVE => 'v4',
        RequestOptions::VERIFY => false,
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

        $portion = $this->linksModel->getCheckPortion(5);
        foreach ($portion as $urlData) {
            $context = null;
            $id = (int) $urlData['id'];
            $url = $urlData['url'];
            $statusCodeOld = $urlData['status'];
            $statusCount = $urlData['status_count'];

            $scheme = parse_url($url, PHP_URL_SCHEME);
            $domain = parse_url($url, PHP_URL_HOST);

            try {
                $response = $this->httpClient->request('GET', $url, self::HTTP_REQUEST_OPTIONS);

                $headersRedirect = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);

                $statusCodeNew = $response->getStatusCode();
                $contentSize = $response->getBody()->getSize();

                $this->logger->info('Зафиксирован редирект', [
                    'headers_redirect' => $headersRedirect,
                    'scheme_old' => $scheme,
                    'domain_old' => $domain,
                ]);
            } catch (BadResponseException $exception) {
                $statusCodeNew = $exception->getResponse()->getStatusCode();
                $contentSize = null;
            } catch (ConnectException $exception) {
                $statusCodeNew = 523;
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
                if ($statusCodeOld !== null) {
                    $this->logger->info('Изменился статус ответа URL', $context);
                }
                $statusCount = 0;
            } else {
                $statusCount++;
            }

            $this->linksModel->updateStatus($id, $statusCodeNew, $statusCount, $contentSize);
        }
    }
}
