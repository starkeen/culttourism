<?php

class MDataCheck extends Model {

    protected $_table_pk = 'dc_id';
    protected $_table_order = 'dc_id';
    protected $_table_active = 'dc_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('data_check');
        $this->_table_fields = array(
            'dc_type',
            'dc_field',
            'dc_item_id',
            'dc_date',
            'dc_result',
        );
        parent::__construct($db);
    }

    public function markChecked($type, $ptid, $field, $result) {
        $this->_db->sql = "INSERT INTO $this->_table_name SET
                            dc_type = '" . $this->_db->getEscapedString($type) . "',
                            dc_field = '" . $this->_db->getEscapedString($field) . "',
                            dc_item_id = '" . $this->_db->getEscapedString($ptid) . "',
                            dc_date = NOW(),
                            dc_result = '" . $this->_db->getEscapedString($result) . "'
                            ON DUPLICATE KEY UPDATE
                            dc_date = NOW(),
                            dc_result = '" . $this->_db->getEscapedString($result) . "'";
        $this->_db->exec();
        $dcid = $this->_db->getLastInserted();
        return $dcid;
    }

}
