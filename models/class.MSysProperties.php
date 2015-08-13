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
        $this->_db->sql = "SELECT sp_name, sp_value FROM $this->_table_name WHERE sp_rs_id = :rsid";
        $this->_db->prepare();
        $this->_db->execute(array(
            ':rsid' => intval($rsid),
        ));
        $config = array();
        while ($row = $this->_db->fetch()) {
            $config[$row['sp_name']] = $row['sp_value'];
        }
        return $config;
    }

    public function getPublic() {
        $this->_db->sql = "SELECT sp_name, sp_value FROM $this->_table_name WHERE sp_public = 1";
        $this->_db->prepare();
        $this->_db->execute(array());
        $config = array();
        while ($row = $this->_db->fetch()) {
            $config[$row['sp_name']] = $row['sp_value'];
        }
        return $config;
    }

    public function updateByName($name, $value) {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET sp_value = :value
                            WHERE sp_name = :name";
        $this->_db->prepare();
        $this->_db->execute(array(
            ':name' => $name,
            ':value' => $value,
        ));
    }

}
