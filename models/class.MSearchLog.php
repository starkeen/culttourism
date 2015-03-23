<?php
class MSearchLog extends Model {
    protected $_table_pk = 'sl_id';
    protected $_table_order = 'sl_date';
    protected $_table_active = 'sl_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('search_log');
        $this->_table_fields = array(
            'sl_date',
            'sl_request',
            'sl_answer',
            'sl_error_code',
            'sl_error_text',
        );
        parent::__construct($db);
    }

    public function add($data) {
        $data['sl_date'] = $this->now();
        return $this->insert($data);
    }
}
?>