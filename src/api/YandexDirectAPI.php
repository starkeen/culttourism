<?php

namespace app\api;

use Throwable;

/**
 * Работа с API Яндекс.Директ
 */
class YandexDirectAPI
{
    const MAX_COUNT = 5;

    protected $token = '';
    protected $urlService = 'https://api.direct.yandex.ru/v4/json/';
    protected $urlOauth = 'https://oauth.yandex.ru/token';

    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Создание нового отчета с несколькими фразами
     *
     * @param string[] $phrases
     *
     * @return int - ID отчета
     * @throws YandexDirectException
     */
    public function createReport(array $phrases = []): int
    {
        $rq = [
            'method' => 'CreateNewWordstatReport',
            'param' => [
                'Phrases' => [],
                'GeoID' => [0],
            ],
        ];
        foreach ($phrases as $phrase) {
            $rq['param']['Phrases'][] = iconv('ISO-8859-1', 'utf-8', $phrase);
        }
        $res = $this->getRequest($rq);
        if (isset($res['data'])) {
            return $res['data'];
        }
        if (isset($res['error_code'])) {
            throw new YandexDirectException('API error:' . $res['error_detail'] ?? 'unknown', $res['error_code']);
        }

        throw new YandexDirectException('Empty DATA response' . print_r($res, true));
    }

    /**
     * Получение данных по отчету
     *
     * @param int $report_id - ID отчета
     *
     * @return array список слов в отчете
     * @throws YandexDirectException
     */
    public function getReport(int $report_id): array
    {
        $res = $this->getRequest(
            [
                'method' => 'GetWordstatReport',
                'param' => $report_id,
            ]
        );
        $reps = [];
        if (isset($res['data'])) {
            foreach ($res['data'] as $data) {
                $rep = ['word' => $data['Phrase'], 'weight' => 0, 'rep_id' => $report_id];
                foreach ($data['SearchedWith'] as $item) {
                    if ($item['Shows'] >= $rep['weight']) {
                        $rep['weight'] = $item['Shows'];
                    }
                }
                $reps[] = $rep;
            }
        } else {
            $ex = new YandexDirectException("Empty data in report $report_id");
            $ex->level = 'warning';
            throw $ex;
        }

        return $reps;
    }

    /**
     * Получить список всех отчетов
     *
     * @return array
     */
    public function getReportsAll(): array
    {
        $res = $this->getRequest(
            [
                'method' => 'GetWordstatReportList',
            ]
        );

        $open_reports = [];
        if (isset($res['data']) && !empty($res['data'])) {
            foreach ((array) $res['data'] as $rep) {
                $open_reports[] = $rep;
            }
        }

        return $open_reports;
    }

    /**
     * Получить список ID готовых отчетов
     *
     * @return array
     */
    public function getReportsDone(): array
    {
        $reports = [];
        foreach ($this->getReportsAll() as $rep) {
            if ($rep['StatusReport'] === 'Done') {
                $reports[] = $rep['ReportID'];
            }
        }
        return $reports;
    }

    /**
     * Общее количество текущих отчетов
     *
     * @return int
     */
    public function getReportsCount(): int
    {
        $all = $this->getReportsAll();

        return count($all);
    }

    /**
     * Количество доступных для создания отчетов
     *
     * @return int
     */
    public function getReportsCountRemain(): int
    {
        return $this->getReportsCountMax() - $this->getReportsCount();
    }

    /**
     * Максимально доступное количество отчетов
     *
     * @return int
     */
    public function getReportsCountMax(): int
    {
        return self::MAX_COUNT;
    }

    /**
     * Удаление отчета по ID
     *
     * @param int $report_id - ID отчета
     *
     * @return array
     */
    public function deleteReport($report_id): array
    {
        return $this->getRequest(
            [
                'method' => 'DeleteWordstatReport',
                'param' => $report_id,
            ]
        );
    }

    /**
     * Количество оставшихся баллов по клиенту
     *
     * @return int
     */
    public function getClientUnits(): int
    {
        $res = $this->getRequest(
            [
                'method' => 'GetClientsUnits',
                //'param' => array('starkeen'),
            ]
        );

        return $res['data'][0]['UnitsRest'] ?? 0;
    }

    /**
     * Получение токена по коду подтверждения
     *
     * @param string $apikey  - ID приложения
     * @param string $apipass - пароль приложения
     * @param string $code    - полученный код
     *
     * @return string - токен
     */
    public function getTokenConfirm($apikey, $apipass, $code)
    {
        $query = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $apikey,
            'client_secret' => $apipass,
        ];
        try {
            $answer = $this->curlPostExec($this->urlOauth, http_build_query($query));
            $response = json_decode($answer);

            return $response->access_token;
        } catch (Throwable $e) {
            echo $e->getMessage();
            exit();
        }
    }

    /**
     * Новый запрос в API Яндекса
     *
     * @param array $request
     *
     * @return array
     */
    protected function getRequest($request)
    {
        $request['locale'] = 'ru';
        $request['token'] = $this->token;
        try {
            $answer = $this->curlPostExec($this->urlService, json_encode($request));
        } catch (Throwable $e) {
            echo $e->getMessage();
            exit();
        }

        return json_decode($answer, true);
    }

    /**
     * Отправка POST-запроса по указанному URL
     *
     * @param string $url  - URL
     *                     запроса
     * @param string $data - данные запроса (json или http_build_query)
     *
     * @return string чистый ответ сервера
     * @throws YandexDirectException
     */
    protected function curlPostExec($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36'
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $text = curl_exec($ch);
        $errno = curl_errno($ch);
        if ($errno) {
            $error_message = curl_error($ch);
            throw new YandexDirectException("cURL error ({$errno}):\n {$error_message}");
        }
        curl_close($ch);

        return $text;
    }
}
