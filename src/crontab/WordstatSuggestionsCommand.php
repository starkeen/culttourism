<?php

declare(strict_types=1);

namespace app\crontab;

use app\api\YandexDirectAPI;
use MWordstat;

class WordstatSuggestionsCommand extends AbstractCrontabCommand
{
    private YandexDirectAPI $api;
    private MWordstat $wordstatModel;

    public function __construct(YandexDirectAPI $api, MWordstat $ws)
    {
        $this->api = $api;
        $this->wordstatModel = $ws;
    }

    public function run(): void
    {
        //****************   1 - Обработка результатов запущеных ранее отчетов *********
        $open_reports = $this->api->getReportsDone();

        $reps = [];
        $reps_to_reset = [];
        $reports = $this->wordstatModel->getProcessingReports();
        foreach ($reports as $row) {
            if (in_array($row['ws_rep_id'], $open_reports, true)) {
                $report_datas = $this->api->getReport($row['ws_rep_id']);
                $reps = array_merge($reps, $report_datas);
            } else {
                $reps_to_reset[] = $row['ws_rep_id'];
            }
        }

        //****************   2 - Сброс очереди зависших отчетов ************************
        if (!empty($reps_to_reset)) {
            $this->wordstatModel->resetQueue($reps_to_reset);
        }

        //****************   3 - Простановка полученных данных по словам **************************
        $reps_to_del = [];
        foreach ($reps as $rep) {
            $city = trim(str_replace('достопримечательности', '', $rep['word']));
            $repid = (int) $rep['rep_id'];
            $this->wordstatModel->setWeight($repid, $city, (int) $rep['weight']);
            $reps_to_del[$repid] = $repid;
        }

        $this->wordstatModel->updateMaxMin();

        //****************   4 - Удаление отработанных отчетов *************************
        foreach ($reps_to_del as $repdel) {
            $this->api->deleteReport($repdel);
        }

        //****************   5 - Постановка новых отчетов в очередь ********************
        $new_reps_cnt = $this->api->getReportsCountRemain();
        $units = $this->api->getClientUnits();

        if ($units > 0 && $new_reps_cnt > 0) {
            for ($i = 1; $i <= $new_reps_cnt; $i++) {
                $portion = $this->wordstatModel->getPortionWeight(5);
                $_ids = [];
                $phrases = [];
                foreach ($portion as $row) {
                    $phrases[] = $row['ws_city_title'] . ' достопримечательности';
                    $_ids[] = $row['ws_id'];
                }
                $created_id = $this->api->createReport($phrases);
                if ($created_id) {
                    $this->wordstatModel->setProcessingReport($_ids, $created_id);
                }
            }
        }
    }
}
