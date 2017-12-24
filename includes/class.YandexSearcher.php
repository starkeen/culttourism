<?php

class YandexSearcher
{

    protected $requestURL = "https://yandex.ru/search/xml?user=starkeen&key=03.10766361:bbf1bd34a06a8c93a745fcca95b31b80&l10n=ru&sortby=rlv&filter=strict";
    protected $meta = [
        'page' => 0,
        'pages' => 20,
        'pages_cnt' => 0,
        'error' => null,
    ];

    /**
     *
     * @var boolean
     */
    protected $loggingEnabled = false;

    /**
     *
     * @var MSearchLog
     */
    protected $logger = null;

    /**
     * Выполнение поискового запроса
     *
     * @param string $request
     *
     * @return array
     * @throws RuntimeException
     */
    public function search($request): array
    {
        $out = [
            'results' => null,
            'pages_cnt' => null,
            'error_code' => null,
            'error_text' => null,
        ];

        $doc = $this->buildQuery($request);

        if ($this->loggingEnabled) {
            $cached = $this->logger->searchByHash($doc);
            if (!empty($cached)) {
                $this->logger->updateHashData($doc);
                $results = $this->parseResponse($cached);
                $out['results'] = $results;
                $out['pages_cnt'] = $this->meta['pages_cnt'];

                return $out;
            }
        }

        try {
            if ($this->loggingEnabled) {
                $this->logger->add(
                    [
                        'sl_query' => $request,
                        'sl_request' => $doc,
                        'sl_error_code' => 0,
                    ]
                );
            }
            $response = $this->getRequest($doc);
            if ($response) {
                if ($this->loggingEnabled) {
                    $this->logger->setAnswer(
                        [
                            'sl_answer' => $response,
                        ]
                    );
                }
                $results = $this->parseResponse($response);

                if (!empty($results)) {
                    $out['results'] = $results;
                    $out['pages_cnt'] = $this->meta['pages_cnt'];
                } elseif (!empty($this->meta['error'])) {
                    $out['error_code'] = $this->meta['error_code'];
                    $out['error_text'] = $this->meta['error_text'];
                    if ($this->loggingEnabled) {
                        $this->logger->setAnswer(
                            [
                                'sl_error_code' => (int) $this->meta['error_code'],
                                'sl_error_text' => (string) $this->meta['error'],
                            ]
                        );
                    }
                }
            } else {
                $out['error_text'] = "Внутренняя ошибка сервера.\n";
                throw new RuntimeException('HTTP request failed!');
            }
        } catch (Exception $e) {
            $out['error_text'] .= ' ' . $e->getMessage();
        }

        return $out;
    }

    /**
     * Установка текущей страницы выдачи
     *
     * @param int $page
     */
    public function setPage(int $page)
    {
        $this->meta['page'] = $page;
    }

    /**
     * Установка максимума страниц в выдаче
     *
     * @param int $max
     */
    public function setPagesMax(int $max)
    {
        $this->meta['pages'] = $max;
    }

    /**
     * Включение лога запросов
     *
     * @param \app\db\MyDB $db
     */
    public function enableLogging($db)
    {
        $this->logger = new MSearchLog($db);
        $this->loggingEnabled = true;
    }

    /**
     * Добавление тегов strong в подсветку
     *
     * @param type $node
     *
     * @return type
     */
    private function highlight($node)
    {
        $stripped = preg_replace('/<\/?(title|passage)[^>]*>/', '', $node->asXML());
        return str_replace('</hlword>', '</strong>', preg_replace('/<hlword[^>]*>/', '<strong>', $stripped));
    }

    /**
     * Очистка строки XML от некоторых тегов
     *
     * @param type $node
     *
     * @return type
     */
    private function clean($node)
    {
        $stripped = preg_replace('/<\/?(title|passage)[^>]*>/', '', $node->asXML());
        return str_replace('</hlword>', '', preg_replace('/<hlword[^>]*>/', '', $stripped));
    }

    /**
     * Построение XML-запроса
     *
     * @param string $request - поисковая строка
     *
     * @return string
     */
    protected function buildQuery($request)
    {
        $query = html_entity_decode($request, ENT_QUOTES, "utf-8");
        $doc = <<<DOC
<?xml version="1.0" encoding="utf-8"?>
<request>
        <query>$query</query>
        <page>{$this->meta['page']}</page>
        <sortby order="descending" priority="no">rlv</sortby>
        <maxpassages>5</maxpassages>
        <groupings>
            <groupby attr="" mode="flat" groups-on-page="{$this->meta['pages']}" docs-in-group="1" curcateg="-1" />
        </groupings>
</request>
DOC;
        return $doc;
    }

    /**
     * Разбор ответа сервера
     *
     * @param string $response
     *
     * @return array
     */
    protected function parseResponse($response)
    {
        $results = [];
        $xmlDoc = new SimpleXMLElement($response);

        $this->meta['error'] = $xmlDoc->response->error;
        $found = $xmlDoc->xpath('response/results/grouping/group/doc');
        if (empty($this->meta['error'])) {
            $this->meta['pages_cnt'] = (int) $xmlDoc->response->found;
            foreach ($found as $item) {
                $result_item = [
                    'domain' => (string) $item->domain,
                    'url' => (string) $item->url,
                    'title' => $this->clean($item->title),
                    'title_hw' => $this->highlight($item->title),
                    'descr' => '',
                    'descr_hw' => '',
                ];
                if ($item->passages) {
                    foreach ($item->passages->passage as $passage) {
                        $result_item['descr'] .= $this->clean($passage) . "\n";
                        $result_item['descr_hw'] .= $this->highlight($passage);
                    }
                }
                $result_item['descr'] = trim($result_item['descr']);
                $result_item['descr_hw'] = trim($result_item['descr_hw']);
                $results[] = $result_item;
            }
        } else {
            $err_attr = $this->meta['error']->attributes();
            $this->meta['error_code'] = $err_attr['code'];
            $this->meta['error_text'] = "Ошибка: " . $this->meta['error'];
        }
        return $results;
    }

    /**
     * Метод для запросов к Яндексу через cURL
     *
     * @param string $data - XML запрос
     *
     * @return string - ответ сервера Яндекса
     * @throws RuntimeException
     */
    protected function getRequest($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $this->requestURL);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/xml',
                'Content-length: ' . strlen($data),
            ]
        );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        //curl_setopt($ch, CURLOPT_INTERFACE, '176.57.209.90'); // 188.225.12.25
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        if ($errno) {
            $error_message = curl_error($ch);
            throw new RuntimeException("cURL error ({$errno}):\n {$error_message}");
        }
        curl_close($ch);

        return $response;
    }

}
