<?php

class MCityData extends Model {

    protected $_table_pk = 'cd_id';
    protected $_table_order = 'cd_id';
    protected $_table_active = 'cd_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('city_data');
        $this->_table_fields = array(
            'cd_id',
            'cd_pc_id',
            'cd_cf_id',
            'cd_value',
        );
        parent::__construct($db);
        $this->_addRelatedTable('city_fields');
    }

    public function getByCityId($cid) {
        $this->_db->sql = "SELECT cf_title, cd_value
                        FROM $this->_table_name cd
                            LEFT JOIN {$this->_tables_related['city_fields']} cf ON cf.cf_id = cd.cd_cf_id
                        WHERE cd.cd_pc_id = :pc_id
                            AND cd.cd_value != ''
                            AND cf.cf_active = 1
                        ORDER BY cf_order";

        $this->_db->execute(array(
            ':pc_id' => $cid,
        ));
        return $this->_db->fetchAll();
    }

}
