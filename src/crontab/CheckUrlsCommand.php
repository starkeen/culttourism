<?php

declare(strict_types=1);

namespace app\crontab;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RedirectMiddleware;
use GuzzleHttp\RequestOptions;
use models\MLinks;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class CheckUrlsCommand extends CrontabCommand
{
    private const HTTP_REQUEST_OPTIONS = [
        RequestOptions::ALLOW_REDIRECTS => [
            'track_redirects' => true,
            'max' => 10,
        ],
        RequestOptions::CONNECT_TIMEOUT => 5,
        RequestOptions::READ_TIMEOUT => 5,
        RequestOptions::TIMEOUT => 5,
        RequestOptions::FORCE_IP_RESOLVE => 'v4',
        RequestOptions::VERIFY => false,
        RequestOptions::HEADERS => [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.75 Safari/537.36 / culttourism bot/1.0',
        ],
    ];

    private const COOKIES_PATH = _DIR_VAR . '/cookies';

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
            $id = (int) $urlData['id'];
            $url = $urlData['url'];
            $statusCodeOld = $urlData['status'];
            $statusCount = $urlData['status_count'];

            $urlScheme = parse_url($url, PHP_URL_SCHEME);
            $urlDomain = parse_url($url, PHP_URL_HOST);
            $redirectUrl = null;

            try {
                if (
                    !file_exists(self::COOKIES_PATH)
                    && !mkdir(self::COOKIES_PATH, 0700, true)
                    && !is_dir(self::COOKIES_PATH)
                ) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', self::COOKIES_PATH));
                }
                $domain = parse_url($url, PHP_URL_HOST);
                if (empty($domain)) {
                    $domain = 'common';
                }
                $cookies = new FileCookieJar(self::COOKIES_PATH . '/' . $domain . '.txt');
                $requestOptions = array_merge(
                    self::HTTP_REQUEST_OPTIONS,
                    [
                        RequestOptions::COOKIES => $cookies,
                    ]
                );
                $response = $this->httpClient->request('GET', $url, $requestOptions);

                $headersRedirect = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);

                $statusCodeNew = $response->getStatusCode();
                $content = $response->getBody()->getContents();
                $contentSize = $response->getBody()->getSize();

                if (!empty($headersRedirect)) {
                    $statusCodeNew = 301;
                    $redirectUrl = array_pop($headersRedirect) ?: null;
                    $redirectUrlScheme = parse_url($redirectUrl, PHP_URL_SCHEME);
                    $redirectUrlDomain = parse_url($redirectUrl, PHP_URL_HOST);
                    if ($redirectUrlDomain === $urlDomain && $redirectUrlScheme !== $urlScheme) {
                        $context1 = [
                            'url' => $url,
                            'redirect' => $redirectUrl,
                            'old' => $statusCodeOld,
                            'page' => $urlData['pt_name'],
                            'city' => $urlData['pc_title_unique'],
                        ];
                        $this->logger->info('Редирект на https', $context1);
                    }
                }
            } catch (BadResponseException $exception) {
                $statusCodeNew = $exception->getResponse()->getStatusCode();
                $content = $exception->getResponse()->getBody()->getContents();
                $contentSize = null;
            } catch (ConnectException $exception) {
                $statusCodeNew = 523;
                $content = null;
                $contentSize = null;
            } catch (RequestException $exception) {
                $context2 = [
                    'url' => $url,
                    'old' => $statusCodeOld,
                    'page' => $urlData['pt_name'],
                    'city' => $urlData['pc_title_unique'],
                    'exception_message' => $exception->getMessage(),
                    'exception_response' => $exception->getResponse(),
                ];
                $this->logger->warning('Сетевая ошибка в запросе', $context2);
                continue;
            } catch (Throwable $exception) {
                $context3 = [
                    'url' => $url,
                    'old' => $statusCodeOld,
                    'page' => $urlData['pt_name'],
                    'city' => $urlData['pc_title_unique'],
                    'exception_message' => $exception->getMessage(),
                ];
                $this->logger->warning('Системная ошибка в сетевом запросе', $context3);
                continue;
            }

            if ($statusCodeOld !== $statusCodeNew) {
                $context4 = [
                    'url' => $url,
                    'page' => $urlData['pt_name'],
                    'city' => $urlData['pc_title_unique'],
                    'old' => $statusCodeOld,
                    'new' => $statusCodeNew,
                    'redirectUrl' => $redirectUrl,
                ];
                if ($statusCodeOld !== null) {
                    $this->logger->info('Изменился статус ответа URL', $context4);
                }
                $statusCount = 0;
            } else {
                $statusCount++;
            }

            $contentTitle = $this->getContentTitle($content);
            $this->linksModel->updateStatus(
                $id,
                $statusCodeNew,
                $statusCount,
                $contentSize,
                $contentTitle,
                $redirectUrl
            );
        }
    }

    /**
     * @param string|null $content
     *
     * @return string|null
     */
    private function getContentTitle(?string $content): ?string
    {
        $result = null;

        if ($content !== null) {
            $matches = [];
            if (preg_match("/<title>([^<]+)<\/title>/is", $content, $matches)) {
                $result = trim($matches[1]);
                $result = strip_tags($result);
                $result = html_entity_decode($result, ENT_QUOTES);
            }
        }

        return $result;
    }
}
