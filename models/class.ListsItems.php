<?php

class ListsItems extends Model {

    protected $_table_pk = 'li_id';
    protected $_table_order = 'li_order';
    protected $_table_active = 'li_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('lists_items');
        $this->_table_fields = array(
            'li_ls_id',
            'li_pt_id',
            'li_order',
            'li_active',
        );
        parent::__construct($db);
    }

    public function deleteByPk($id) {
        $this->updateByPk($id, array($this->_table_active => 0));
    }

}
