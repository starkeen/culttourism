<?php

/**
 * Description of class MSysProperties
 *
 * @author Андрей
 */
class MSysProperties extends Model {

    protected $_table_pk = 'sp_id';
    protected $_table_order = 'sp_name';
    protected $_table_active = 'sp_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('siteprorerties');
        $this->_table_fields = array(
            //'sp_name',
            //'sp_rs_id',
            'sp_value',
                //'sp_title',
                //'sp_public',
                //'sp_whatis',
        );
        parent::__construct($db);
    }

    public function getSettingsByBranchId($rsid) {
        $this->_db->sql = "SELECT * FROM $this->_table_name WHERE sp_rs_id = " . intval($rsid);
        $this->_db->exec();
        $config = array();
        while ($row = $this->_db->fetch()) {
            $config[$row['sp_name']] = $row['sp_value'];
        }
        return $config;
    }

    public function getPublic() {
        $this->_db->sql = "SELECT * FROM $this->_table_name WHERE sp_public = 1";
        $this->_db->exec();
        $config = array();
        while ($row = $this->_db->fetch()) {
            $config[$row['sp_name']] = $row['sp_value'];
        }
        return $config;
    }

    public function updateByName($name, $value) {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET sp_value = '" . $this->escape($value) . "'
                            WHERE sp_name = '" . $this->escape($name) . "'";
        $this->_db->exec();
    }

}
