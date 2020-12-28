<?php

use app\db\MyDB;

class MWordstatTrends extends Model
{
    protected $_table_pk = 'wt_id';
    protected $_table_order = 'wt_date';
    protected $_table_active = 'wt_id';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('wordstat_trends');
        $this->_table_fields = [
            'wt_date',
            'wt_sum',
            'wt_count',
            'wt_avg',
            'wp_positions_avg',
        ];
        parent::__construct($db);
        $this->addRelatedTable('wordstat');
    }

    public function calcToday(): void
    {
        $this->_db->sql = "INSERT IGNORE INTO {$this->_table_name}
                          (wt_date, wt_sum, wt_count, wt_avg, wp_positions_avg)
                          (
                            SELECT NOW(), SUM(ws_weight), COUNT(ws_id), ROUND(AVG(ws_weight)), ROUND(AVG(ws_position))
                            FROM {$this->_tables_related['wordstat']}
                          )";
        $this->_db->exec();
    }
}
