<?php

use app\db\MyDB;

class MWordstat extends Model
{
    protected $_table_pk = 'ws_id';
    protected $_table_order = 'ws_id';
    protected $_table_active = 'ws_id';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('wordstat');
        $this->_table_fields = [
            'ws_id',
            'ws_city_id',
            'ws_city_title',
            'ws_rep_id',
            'ws_weight',
            'ws_weight_date',
            'ws_weight_max',
            'ws_weight_max_date',
            'ws_weight_min',
            'ws_weight_min_date',
            'ws_position',
            'ws_position_date',
            'ws_position_last',
        ];
        parent::__construct($db);
        $this->addRelatedTable('pagecity');
        $this->addRelatedTable('ref_city');
        $this->addRelatedTable('ref_region');
        $this->addRelatedTable('ref_country');
    }

    /**
     * Статистика по популярности городов в поиске
     * @return array
     */
    public function getStatPopularity(): array
    {
        $this->_db->sql = "SELECT ws_id, ws_city_title AS city_name,
                                rr.name AS region_name, co.name AS country_name,
                                ws_city_id, ws_weight, ws.ws_weight_date,
                                ws_weight_min, ws.ws_weight_min_date,
                                ws_weight_max, ws.ws_weight_max_date,
                                ROUND(100*(ws_weight_max - ws_weight) / ws_weight) AS weight_delta_max,
                                ROUND(100*(ws_weight - ws_weight_min) / ws_weight) AS weight_delta_min,
                                0 AS weight_delta_sign
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_city_id = ws.ws_city_id
                                LEFT JOIN {$this->_tables_related['ref_city']} rc ON rc.id = ws.ws_city_id
                                    LEFT JOIN {$this->_tables_related['ref_region']} rr ON rr.id = rc.region_id
                                    LEFT JOIN {$this->_tables_related['ref_country']} co ON co.id = rc.country_id
                            WHERE ws_weight > 0
                                AND pc_id IS NULL
                            ORDER BY (ws_weight_max+ws_weight_min)/2 DESC, ws_weight_min DESC, ws_weight_max DESC, ws_weight DESC
                            LIMIT 80";
        $this->_db->exec();
        $stat = [];
        while ($row = $this->_db->fetch()) {
            if ($row['weight_delta_max'] > $row['weight_delta_min'] && $row['weight_delta_min'] > 10) {
                $row['weight_delta_sign'] = -1;
            } elseif ($row['weight_delta_max'] < $row['weight_delta_min'] && $row['weight_delta_max'] > 10) {
                $row['weight_delta_sign'] = 1;
            } else {
                $row['weight_delta_sign'] = 0;
            }
            $stat[] = $row;
        }

        return $stat;
    }

    /**
     * Статистика по позициям имеющихся страниц
     * @return array
     */
    public function getStatPositions(): array
    {
        $this->_db->sql = "SELECT ws_city_title AS city_name, rr.name AS region_name, co.name AS country_name,
                                pc.pc_add_date, ws_city_id,
                                ws_weight, ws.ws_weight_date,
                                ws_position, ws.ws_position_date,
                                ws_weight_max, ws.ws_weight_max_date,
                                ws_weight_min, ws.ws_weight_min_date,
                                ROUND(ws_weight_max/1000) AS weight_x1000,
                                ROUND(ws_weight_max/100) AS weight_x100,
                                ROUND(100*(ws_weight_max - ws_weight) / ws_weight) AS weight_delta_max,
                                ROUND(100*(ws_weight - ws_weight_min) / ws_weight) AS weight_delta_min,
                                0 AS weight_delta_sign,
                                IF(ws_position = 0, 101, ws_position) AS ws_position_real,
                                IF(ws_position = 0, '&mdash;', ws_position) AS ws_position,
                                IF(ws_position = 0, 100, IF(ws_position > 50, 100, IF(ws_position > 20, 50, IF(ws_position > 10, 20, 10)))) AS position_x
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_city_id = ws.ws_city_id
                                LEFT JOIN {$this->_tables_related['ref_city']} rc ON rc.id = ws.ws_city_id
                                    LEFT JOIN {$this->_tables_related['ref_region']} rr ON rr.id = rc.region_id
                                    LEFT JOIN {$this->_tables_related['ref_country']} co ON co.id = rc.country_id
                            WHERE ws_weight > 0
                                AND pc_id IS NOT NULL
                                AND ws_position IS NOT NULL
                                AND (ws_position > 10 OR ws_position = 0)
                            GROUP BY city_name
                            ORDER BY ws_weight_min DESC, ws_position_real DESC
                            LIMIT 100";
        $this->_db->exec();
        $seo = [];
        while ($row = $this->_db->fetch()) {
            if ($row['weight_delta_max'] > $row['weight_delta_min'] && $row['weight_delta_min'] > 10) {
                $row['weight_delta_sign'] = -1;
            } elseif ($row['weight_delta_max'] < $row['weight_delta_min'] && $row['weight_delta_max'] > 10) {
                $row['weight_delta_sign'] = 1;
            } else {
                $row['weight_delta_sign'] = 0;
            }
            $seo[] = $row;
        }

        return $seo;
    }

    /**
     * Порция городов для проверки позиций
     * @param integer $limit
     * @return array
     */
    public function getPortionPosition($limit = 10): array
    {
        $this->_db->sql = "SELECT ws_id, ws_city_title
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc
                                    ON pc.pc_city_id = ws.ws_city_id
                            WHERE pc.pc_id IS NOT NULL
                            ORDER BY ws_position_date, pc_rank DESC
                            LIMIT :limit";
        $this->_db->execute([
            ':limit' => $limit,
        ]);

        return $this->_db->fetchAll();
    }

    /**
     * Порция городов для проверки их весов
     * @param integer $limit
     * @return array
     */
    public function getPortionWeight($limit = 5): array
    {
        $this->_db->sql = "SELECT ws.*
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_city_id = ws.ws_city_id
                            WHERE ws_rep_id = 0
                            ORDER BY ws_weight_date, pc_rank DESC
                            LIMIT :limit";
        $this->_db->execute([
            ':limit' => $limit,
        ]);

        return $this->_db->fetchAll();
    }

    /**
     * Простановка веса по полученным данным
     * @param integer $rep_id
     * @param string $city
     * @param integer $weight
     */
    public function setWeight($rep_id, $city, $weight)
    {
        $this->_db->sql = "UPDATE $this->_table_name
                                SET ws_weight = :weight, ws_weight_date = NOW(), ws_rep_id = 0
                            WHERE ws_rep_id = :rep_id
                                AND ws_city_title = :city";
        $this->_db->execute([
            ':rep_id' => $rep_id,
            ':city' => $city,
            ':weight' => $weight,
        ]);
    }

    /**
     * Простановка словам ID отчетов в работе
     * @param array $ids
     * @param integer $report_id
     */
    public function setProcessingReport($ids, $report_id)
    {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET ws_rep_id = :report_id
                            WHERE FIND_IN_SET(CAST(ws_id AS char), :ids)";
        $this->_db->execute([
            ':report_id' => $report_id,
            ':ids' => implode(',', $ids),
        ]);
    }

    /**
     * Получить отчёты в работе
     * @return array
     */
    public function getProcessingReports()
    {
        $this->_db->sql = "SELECT ws_rep_id
                            FROM $this->_table_name
                            WHERE ws_rep_id != 0
                            GROUP BY ws_rep_id";
        $this->_db->exec();

        return $this->_db->fetchAll();
    }

    /**
     * Сброс очереди отчетов, например если они зависли
     * @param array $rep_ids
     */
    public function resetQueue($rep_ids)
    {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET ws_rep_id = 0
                            WHERE FIND_IN_SET(CAST(ws_rep_id AS char), :ids)";
        $this->_db->execute([
            ':ids' => implode(',', $rep_ids),
        ]);
    }

    /**
     * Простановка свежих максимумов и минимумов
     */
    public function updateMaxMin()
    {
        $this->_db->sql = "UPDATE $this->_table_name
                                SET ws_weight_max = ws_weight, ws_weight_max_date = NOW()
                            WHERE ws_weight > ws_weight_max";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name
                                SET ws_weight_min = ws_weight, ws_weight_min_date = NOW()
                            WHERE ws_weight < ws_weight_min";
        $this->_db->exec();
    }

    /**
     * Сбросить все отчеты по запросам
     */
    public function resetWeightsAll()
    {
        $this->_db->sql = "UPDATE $this->_table_name SET ws_weight = -1, ws_rep_id = 0";
        $this->_db->exec();
    }

    /**
     * Сбросить все веса запросов по отчету
     * @param int $report_id
     */
    public function resetWeightReport($report_id)
    {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET ws_weight = -1
                            WHERE ws_rep_id = :report";
        $this->_db->execute([
            ':report' => (int) $report_id,
        ]);
    }

    /**
     * Общее число записей
     * @return int
     */
    public function getStatTowns()
    {
        $this->_db->sql = "SELECT count(*) AS cnt FROM $this->_table_name";
        $this->_db->exec();
        $row = $this->_db->fetch();

        return $row['cnt'];
    }

    /**
     * Число добавленных страниц
     * @return int
     */
    public function getStatBase()
    {
        $this->_db->sql = "SELECT count(*) AS cnt
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc
                                    ON pc.pc_city_id = ws.ws_city_id
                            WHERE pc_id IS NOT NULL";
        $this->_db->exec();
        $row = $this->_db->fetch();

        return $row['cnt'];
    }

    /**
     * Число необработанных записей по запросам
     * @return int
     */
    public function getStatRemain()
    {
        $this->_db->sql = "SELECT count(*) AS cnt
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc
                                    ON pc.pc_city_id = ws.ws_city_id
                            WHERE pc_id IS NOT NULL
                                AND ws_weight = -1";
        $this->_db->exec();
        $row = $this->_db->fetch();

        return $row['cnt'];
    }

    /**
     * Число проиндексированных поисковиками записей
     * @return int
     */
    public function getStatIndexed()
    {
        $this->_db->sql = "SELECT count(*) AS cnt
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc
                                    ON pc.pc_city_id = ws.ws_city_id
                            WHERE pc_id IS NOT NULL
                                AND ws_position IS NOT NULL";
        $this->_db->exec();
        $row = $this->_db->fetch();

        return $row['cnt'];
    }

    /**
     * Распределение позиций по диапазонам
     * @return array
     */
    public function getStatPositionsRanges(): array
    {
        $out = [
            'none' => 0,
            '10' => 0,
            '20' => 0,
            '50' => 0,
        ];
        $this->_db->sql = "SELECT count(*) AS cnt,
                                IF(ws_position = 0, 0, IF(ws_position > 50, 0, IF(ws_position > 20, 50, IF(ws_position > 10, 20, 10)))) AS xtop
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc
                                    ON pc.pc_city_id = ws.ws_city_id
                            WHERE pc_id IS NOT NULL
                                AND ws_position IS NOT NULL
                            GROUP BY xtop";
        $this->_db->exec();
        while ($row = $this->_db->fetch()) {
            if ($row['xtop'] == 0) {
                $out['none'] += $row['cnt'];
            }
            if ($row['xtop'] == 10) {
                $out['10'] += $row['cnt'];
            }
            if ($row['xtop'] == 20) {
                $out['20'] += $row['cnt'];
            }
            if ($row['xtop'] == 50) {
                $out['50'] += $row['cnt'];
            }
        }

        return $out;
    }

    /**
     * Статистика по минимальным датам
     * @return array
     */
    public function getStatDates(): array
    {
        $this->_db->sql = "SELECT DATE_FORMAT(MIN(ws_weight_date), '%d.%m.%Y') AS min_weight,
                                DATE_FORMAT(MIN(ws_position_date), '%d.%m.%Y') AS min_position
                            FROM $this->_table_name ws";
        $this->_db->exec();

        return $this->_db->fetch();
    }
}
