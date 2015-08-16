<?php

class MWordstat extends Model {

    protected $_table_pk = 'ws_id';
    protected $_table_order = 'ws_id';
    protected $_table_active = 'ws_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('wordstat');
        $this->_table_fields = array(
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
        );
        parent::__construct($db);
        $this->_addRelatedTable('pagecity');
    }

    /**
     * Порция городов для проверки позиций
     * @param integer $limit
     * @return array
     */
    public function getPortionPosition($limit = 10) {
        $this->_db->sql = "SELECT ws_id, ws_city_title
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc
                                    ON pc.pc_city_id = ws.ws_city_id
                            WHERE pc.pc_id IS NOT NULL
                            ORDER BY ws_position_date, pc_rank DESC
                            LIMIT :limit";
        $this->_db->execute(array(
            ':limit' => $limit,
        ));
        return $this->_db->fetchAll();
    }

    /**
     * Порция городов для проверки их весов
     * @param integer $limit
     * @return array
     */
    public function getPortionWeight($limit = 5) {
        $this->_db->sql = "SELECT ws.*
                            FROM $this->_table_name ws
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_city_id = ws.ws_city_id
                            WHERE ws_rep_id = 0
                            ORDER BY ws_weight_date, pc_rank DESC
                            LIMIT :limit";
        $this->_db->execute(array(
            ':limit' => $limit,
        ));
        return $this->_db->fetchAll();
    }

    /**
     * Простановка веса по полученным данным
     * @param integer $rep_id
     * @param string $city
     * @param integer $weight
     */
    public function setWeight($rep_id, $city, $weight) {
        $this->_db->sql = "UPDATE $this->_table_name
                                SET ws_weight = :weight, ws_weight_date = NOW(), ws_rep_id = 0
                            WHERE ws_rep_id = :rep_id
                                AND ws_city_title = :city";
        $this->_db->execute(array(
            ':rep_id' => $rep_id,
            ':city' => $city,
            ':weight' => $weight,
        ));
    }

    /**
     * Простановка словам ID отчетов в работе
     * @param array $ids
     * @param integer $report_id
     */
    public function setProcessingReport($ids, $report_id) {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET ws_rep_id = :report_id
                            WHERE FIND_IN_SET(CAST(ws_id AS char), :ids)";
        $this->_db->execute(array(
            ':report_id' => $report_id,
            ':ids' => implode(',', $ids),
        ));
    }

    /**
     * Получить отчёты в работе
     * @return array
     */
    public function getProcessingReports() {
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
    public function resetQueue($rep_ids) {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET ws_rep_id = 0
                            WHERE FIND_IN_SET(CAST(ws_rep_id AS char), :ids)";
        $this->_db->execute(array(
            ':ids' => implode(',', $rep_ids),
        ));
    }

    /**
     * Простановка свежих максимумов и минимумов
     */
    public function updateMaxMin() {
        $this->_db->sql = "UPDATE $this->_table_name
                                SET ws_weight_max = ws_weight, ws_weight_max_date = NOW()
                            WHERE ws_weight > ws_weight_max";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name
                                SET ws_weight_min = ws_weight, ws_weight_min_date = NOW()
                            WHERE ws_weight < ws_weight_min";
        $this->_db->exec();
    }

}
