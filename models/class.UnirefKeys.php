<?php

class UnirefKeys extends Model {

    protected $_table_pk = 'uk_id';
    protected $_table_order = 'uk_order';
    protected $_table_active = 'uk_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('uniref_keys');
        $this->_table_fields = array(
        );
        parent::__construct($db);
    }

    public function getAll() {
        $uvt = $this->_db->getTableName('uniref_values');
        $this->_db->sql = "SELECT *,
                            (SELECT count(*) FROM $uvt WHERE uv_uk_id = uk_id) AS children_cnt
                            FROM $this->_table_name\n";
        if ($this->_table_order)
            $this->_db->sql .= "ORDER BY $this->_table_order ASC\n";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

}

?>
