<?php

class MNewsItems extends Model {

    protected $_table_pk = 'ni_id';
    protected $_table_order = 'ni_id';
    protected $_table_active = 'ni_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('news_items');
        $this->_table_fields = array(
            'ni_id',
            'ni_ns_id',
            'ni_pubdate',
            'ni_title',
            'ni_url',
            'ni_text',
            'ni_active',
        );
        parent::__construct($db);
    }

    public function cleanExpired() {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE ni_pubdate < SUBDATE(NOW(), INTERVAL 3 DAY)";
        $this->_db->exec();
    }

}
