<?php
class MSearchLog extends Model {
    protected $_table_pk = 'sl_id';
    protected $_table_order = 'sl_date';
    protected $_table_active = 'sl_id';
    
    private $_record_id = null;

    public function __construct($db) {
        $this->_table_name = $db->getTableName('search_log');
        $this->_table_fields = array(
            'sl_date',
            'sl_query',
            'sl_request',
            'sl_answer',
            'sl_error_code',
            'sl_error_text',
        );
        parent::__construct($db);
    }

    public function add($data) {
        $data['sl_date'] = $this->now();
        $this->_record_id = $this->insert($data);
        return $this->_record_id;
    }
    
    public function setAnswer($data) {
        $this->updateByPk($this->_record_id, $data);
    }
}
?>