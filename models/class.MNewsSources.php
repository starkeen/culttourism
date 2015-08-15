<?php

class MPages extends Model {

    protected $_table_pk = 'ns_id';
    protected $_table_order = 'ns_id';
    protected $_table_active = 'ns_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('news_sourses');
        $this->_table_fields = array(
            'ns_id',
            'ns_title',
            'ns_web',
            'ns_url',
            'ns_last_read',
            'ns_active',
        );
        parent::__construct($db);
    }

}
