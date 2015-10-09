<?php

class YandexDirectAPI {

    const MAX_COUNT = 5;

    protected $token = '';
    protected $urlService = 'https://api.direct.yandex.ru/v4/json/';
    protected $urlOauth = 'https://oauth.yandex.ru/token';

    public function __construct($token) {
        $this->token = $token;
    }

    /**
     * Создание нового отчета с несколькими фразами
     * @param string[] $phrases
     * @return int - ID отчета
     * @throws Exception
     */
    public function createReport($phrases = array()) {
        $rq = array(
            'method' => 'CreateNewWordstatReport',
            'param' => array(
                'Phrases' => array(),
                'GeoID' => array(0),
            ),
        );
        foreach ($phrases as $phrase) {
            $rq['param']['Phrases'][] = iconv('ISO-8859-1', 'utf-8', $phrase);
        }
        $res = $this->getRequest($rq);
        if (isset($res['data'])) {
            return $res['data'];
        } else {
            throw new Exception("Error1 (invalid sert?)");
        }
    }

    /**
     * Получение данных по отчету
     * @param int $report_id - ID отчета
     * @return array список слов в отчете
     * @throws Exception
     */
    public function getReport($report_id) {
        $res = $this->getRequest(array(
            'method' => 'GetWordstatReport',
            'param' => $report_id,
        ));
        $reps = array();
        if (isset($res['data'])) {
            foreach ($res['data'] as $data) {
                $rep = array('word' => $data['Phrase'], 'weight' => 0, 'rep_id' => $report_id);
                foreach ($data['SearchedWith'] as $item) {
                    if ($item['Shows'] >= $rep['weight']) {
                        $rep['weight'] = $item['Shows'];
                    }
                }
                $reps[] = $rep;
            }
        } else {
            throw new Exception("Error2 in $report_id");
        }
        return $reps;
    }

    /**
     * Получить список всех отчетов
     * @return array
     */
    public function getReportsAll() {
        $res = $this->getRequest(array(
            'method' => 'GetWordstatReportList',
        ));
        $open_reports = array();
        if (isset($res['data']) && !empty($res['data'])) {
            foreach ($res['data'] as $rep) {
                $open_reports[] = $rep;
            }
        }
        return $open_reports;
    }

    /**
     * Получить список ID готовых отчетов
     * @return array
     */
    public function getReportsDone() {
        $reports = array();
        foreach ($this->getReportsAll() as $rep) {
            if ($rep['StatusReport'] == 'Done') {
                $reports[] = $rep['ReportID'];
            }
        }
        return $reports;
    }

    /**
     * Общее количество текущих отчетов
     * @return int
     */
    public function getReportsCount() {
        $all = $this->getReportsAll();
        $count = count($all);
        return $count;
    }

    /**
     * Количество доступных для создания отчетов
     * @return int
     */
    public function getReportsCountRemain() {
        return $this->getReportsCountMax() - $this->getReportsCount();
    }

    /**
     * Максимально доступное количество отчетов
     * @return int
     */
    public function getReportsCountMax() {
        return self::MAX_COUNT;
    }

    /**
     * Удаление отчета по ID
     * @param int $report_id - ID отчета
     * @return array
     */
    public function deleteReport($report_id) {
        return $this->getRequest(array(
                    'method' => 'DeleteWordstatReport',
                    'param' => $report_id,
        ));
    }

    /**
     * Количество оставшихся баллов по клиенту
     * @return int
     */
    public function getClientUnits() {
        $res = $this->getRequest(array(
            'method' => 'GetClientsUnits',
                //'param' => array('starkeen'),
        ));
        return isset($res['data'][0]['UnitsRest']) ? $res['data'][0]['UnitsRest'] : 0;
    }

    /**
     * Получение токена по коду подтверждения
     * @param string $apikey - ID приложения
     * @param string $apipass - пароль приложения
     * @param string $code - полученный код
     * @return string - токен
     */
    public function getTokenConfirm($apikey, $apipass, $code) {
        $query = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $apikey,
            'client_secret' => $apipass,
        );
        try {
            $answer = $this->curlPostExec($this->urlOauth, http_build_query($query));
            $response = json_decode($answer);
            return $response->access_token;
        } catch (Exception $e) {
            echo $e->getMessage();
            exit();
        }
    }

    /**
     * Новый запрос в API Яндекса
     * @param array $request
     * @return array
     */
    protected function getRequest($request) {
        $request['locale'] = 'ru';
        $request['token'] = $this->token;
        try {
            $answer = $this->curlPostExec($this->urlService, json_encode($request));
        } catch (Exception $e) {
            echo $e->getMessage();
            exit();
        }
        return json_decode($answer, true);
    }

    /**
     * Отправка POST-запроса по указанному URL
     * @param string $url - URL запроса
     * @param string $data - данные запроса (json или http_build_query)
     * @return string чистый ответ сервера
     * @throws Exception
     */
    protected function curlPostExec($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $text = curl_exec($ch);
        $errno = curl_errno($ch);
        if ($errno) {
            $error_message = curl_error($ch);
            throw new Exception("cURL error ({$errno}):\n {$error_message}");
        }
        curl_close($ch);

        return $text;
    }

}
