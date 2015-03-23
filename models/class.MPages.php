<?php

class MPages extends Model {

    protected $_table_pk = 'pg_id';
    protected $_table_order = 'pg_id';
    protected $_table_active = 'pg_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('pages');
        $this->_table_fields = array(
            'sc_id',
            'sc_citypage_id',
            'sc_date',
            'sc_hash',
        );
        parent::__construct($db);
    }

}
