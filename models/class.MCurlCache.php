<?php

class MCurlCache extends Model {

    protected $_table_pk = 'cc_id';
    protected $_table_order = 'cc_date';
    protected $_table_active = 'cc_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('curl_cache');
        $this->_table_fields = array(
            'cc_date',
            'cc_url',
            'cc_text',
        );
        parent::__construct($db);
    }

    public function get($url) {
        $this->_db->sql = "SELECT * FROM $this->_table_name WHERE cc_url = '" . $this->escape($url) . "'";
        $this->_db->exec();
        $row = $this->_db->fetch();
        return !empty($row) ? $row['cc_text'] : null;
    }

    public function put($url, $text) {
        $this->_db->sql = "INSERT INTO $this->_table_name
                            SET
                                cc_date = NOW(),
                                cc_url = '" . $this->escape($url) . "',
                                cc_text = '" . $this->escape($text) . "'
                            ON DUPLICATE KEY UPDATE cc_date = NOW(), cc_text = '" . $this->escape($text) . "'";
        $this->_db->exec();
    }

}
