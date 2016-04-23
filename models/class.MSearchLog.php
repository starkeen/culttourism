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
            'sl_query_hash',
            'sl_request',
            'sl_answer',
            'sl_error_code',
            'sl_error_text',
        );
        parent::__construct($db);
    }

    public function add($data) {
        $data['sl_date'] = $this->now();
        $data['sl_query_hash'] = self::getQueryHash($data['sl_query']);
        $this->_record_id = $this->insert($data);
        return $this->_record_id;
    }

    public function setAnswer($data) {
        $this->updateByPk($this->_record_id, $data);
    }

    /**
     * Поиск запросов в логе
     * @param string $query
     */
    public function searchByQuery($query) {
        $this->_db->sql = "SELECT * FROM $this->_table_name WHERE sl_query_hash = :hash";
        $this->_db->execute(array(
            ':hash' => self::getQueryHash($query),
        ));
        $row = $this->_db->fetch();
        return $row['sl_answer'];
    }

    /**
     * Индексируемый ключ уникализации запроса
     * @param type $query
     */
    public static function getQueryHash($query) {
        $lower = mb_strtolower($query);
        $symbols = preg_replace('|s+|', '', $lower);
        $trimmed = trim($symbols);
        return sha1($trimmed);
    }

}
