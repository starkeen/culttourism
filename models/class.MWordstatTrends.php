<?php

class MWordstatTrends extends Model {

    protected $_table_pk = 'wt_id';
    protected $_table_order = 'wt_date';
    protected $_table_active = 'wt_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('wordstat_trends');
        $this->_table_fields = array(
            'wt_date',
            'wt_sum',
            'wt_count',
            'wt_avg',
        );
        parent::__construct($db);
        $this->_addRelatedTable('wordstat');
    }

    public function calcToday()
    {
        $this->db->sql = "INSERT INTO {$this->_table_name}
                          (wt_date, wt_sum, wt_count, wt_avg)
                          (
                            SELECT NOW(), SUM(ws_weight), COUNT(ws_id), ROUND(AVG(ws_weight))
                            FROM {$this->_tables_related['wordstat']}
                          )";
        $this->_db->exec();
    }
}
