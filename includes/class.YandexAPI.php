<?php

class YandexAPI {

    const MAX_COUNT = 5;

    protected $apikey = '';
    protected $url = 'https://api.direct.yandex.ru/v4/json/';

    public function __construct() {
        $this->apikey = '';
    }

    public function createReport($phrases = array()) {
        $request_create = array(
            'method' => 'CreateNewWordstatReport',
            'param' => array(
                'Phrases' => array(),
                'GeoID' => array(0),
            ),
        );
        foreach ($phrases as $phrase) {
            $request_create['param']['Phrases'][] = iconv('ISO-8859-1', 'utf-8', $phrase);
        }
        $res_create = $this->getRequestOld($request_create);
        if (isset($res_create['data'])) {
            return $res_create['data'];
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
        $request_report = array(
            'method' => 'GetWordstatReport',
            'param' => $report_id,
        );
        $res_report = $this->getRequestOld($request_report);
        $reps = array();
        if (isset($res_report['data'])) {
            foreach ($res_report['data'] as $data) {
                $rep = array('word' => $data['Phrase'], 'weight' => 0, 'rep_id' => $row['ws_rep_id']);
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
     * Получить список готовых отчетов
     * @return array
     */
    public function getReportsDone() {
        $request_active = array(
            'method' => 'GetWordstatReportList',
        );
        $res_opened = $this->getRequestOld($request_active);
        $open_reports = array();
        if (isset($res_opened['data']) && !empty($res_opened['data'])) {
            foreach ($res_opened['data'] as $rep) {
                if ($rep['StatusReport'] == 'Done') {
                    $open_reports[] = $rep['ReportID'];
                }
            }
        }
        return $open_reports;
    }

    /**
     * Общее количество текущих отчетов
     * @return int
     */
    public function getReportsCount() {
        $request_count = array(
            'method' => 'GetWordstatReportList',
        );
        $res_count = $this->getRequestOld($request_count);
        $count = 0;
        if (!empty($res_count['data'])) {
            foreach ($res_count['data'] as $rep) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Количество доступных для создания отчетов
     * @return int
     */
    public function getReportsCountRemain() {
        $new_reps_cnt = $this->getReportsCountMax();
        $res_count = $this->getRequestOld($request_count);
        $count = 0;
        if (!empty($res_count['data'])) {
            foreach ($res_count['data'] as $rep) {
                $new_reps_cnt--;
            }
        }
        return $count;
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
        return $this->getRequestOld(array(
                    'method' => 'DeleteWordstatReport',
                    'param' => $report_id,
        ));
    }

    /**
     * Старый запрос в API Яндекса 
     * @param array $request
     * @return array
     */
    protected function getRequestOld($request) {
        $url = "https://api.direct.yandex.ru/v4/json/";
        $request['locale'] = 'ru';
        $opts = array(
            'http' => array(
                'method' => "POST",
                'content' => json_encode($request),
            )
        );
        $context = stream_context_create($opts);
        stream_context_set_option($context, 'ssl', 'local_cert', _DIR_ROOT . '/data/private/api-yandex/solid-cert.crt');
        $result = @file_get_contents($url, 0, $context);

        return json_decode($result, true);
    }

    /**
     * Новый запрос в API Яндекса
     * @param array $request
     * @return array
     */
    protected function getRequest($request) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36');

        curl_setopt($ch, CURLOPT_URL, $this->url);
        try {
            $text = curl_exec($ch);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        curl_close($ch);

        return json_decode($text, true);
    }

}
