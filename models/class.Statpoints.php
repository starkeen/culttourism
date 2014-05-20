<?php

class Statpoints extends Model {

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
        $this->insert(array(
            'sp_pagepoint_id' => $point_id,
            'sp_date' => date('Y-m-d H:i:s'),
            'sp_hash' => $hash,
        ));
        return true;
    }

}
