<?php

class MStatpoints extends Model {

    protected $_table_pk = 'sp_id';
    protected $_table_order = 'sp_id';
    protected $_table_active = 'sp_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('statpoints');
        $this->_table_fields = array(
            'sp_id',
            'sp_pagepoint_id',
            'sp_date',
            'sp_hash',
        );
        parent::__construct($db);
    }

    public function add($point_id, $hash) {
        $this->_db->sql = "INSERT INTO $this->_table_name SET
                            sp_pagepoint_id = :point_id,
                            sp_date = NOW(),
                            sp_hash = :hash
                           ON DUPLICATE KEY UPDATE sp_date = NOW()";
        
        $this->_db->execute(array(
           ':hash' => $hash,
           ':point_id' => $point_id,
        ));
        return true;
    }

}
