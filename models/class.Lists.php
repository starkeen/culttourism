<?php

class Lists extends Model {

    protected $_table_pk = 'ls_id';
    protected $_table_order = 'ls_order';
    protected $_table_active = 'ls_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('lists');
        $this->_table_fields = array(
            'ls_title',
            'ls_slugline',
            'ls_keywords',
            'ls_description',
            'ls_text',
            'ls_order',
            'ls_active',
        );
        parent::__construct($db);
    }

    public function deleteByPk($id) {
        $this->updateByPk($id, array($this->_table_active => 0));
    }

}
