<?php

class MCityFields extends Model {

    protected $_table_pk = 'cf_id';
    protected $_table_order = 'cf_order';
    protected $_table_active = 'cf_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('city_fields');
        $this->_table_fields = array(
            'cf_id',
            'cf_title',
            'cf_order',
            'cf_active',
        );
        parent::__construct($db);
    }

}
