<?php

class MCron extends Model {

    protected $_table_pk = 'cr_id';
    protected $_table_order = 'cr_id';
    protected $_table_active = 'cr_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('cron');
        $this->_table_fields = array(
            'cr_id',
            'cr_title',
            'cr_script',
            'cr_datelast',
            'cr_datelast_attempt',
            'cr_datenext',
            'cr_period',
            'cr_lastresult',
            'cr_lastexectime,
            'cr_isrun',
            'cr_active',
        );
        parent::__construct($db);
    }

}