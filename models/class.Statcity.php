<?php

class Statcity extends Model {

    protected $_table_pk = 'sc_id';
    protected $_table_order = 'sc_id';
    protected $_table_active = 'sc_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('statcity');
        $this->_table_fields = array(
            'sc_id',
            'sc_citypage_id',
            'sc_date',
            'sc_hash',
        );
        parent::__construct($db);
    }

    public function add($city_id, $hash) {
        $this->insert(array(
            'sc_citypage_id' => $city_id,
            'sc_date' => date('Y-m-d H:i:s'),
            'sc_hash' => $hash,
        ));
        return true;
    }

}
