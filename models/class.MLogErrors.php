<?php

class MLogErrors extends Model {

    protected $_table_pk = 'le_id';
    protected $_table_order = 'le_id';
    protected $_table_active = 'le_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('log_errors');
        $this->_table_fields = array(
            'le_id',
            'le_type',
            'le_date',
            'le_url',
            'le_ip',
            'le_browser',
            'le_script',
            'le_referer',
        );
        parent::__construct($db);
    }

}
