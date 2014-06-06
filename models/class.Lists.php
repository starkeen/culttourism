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

    public function getItemBySlugline($slugline) {
        $out = array();
        $dbi = $this->_db->getTableName('lists_items');
        $this->_db->sql = "SELECT ls.*,
                                (SELECT COUNT(*) FROM $dbi WHERE li_ls_id = ls.ls_id) AS cnt,
                                CHAR_LENGTH(TRIM(ls_description)) AS len_descr,
                                CHAR_LENGTH(TRIM(ls_text)) AS len_text
                            FROM $this->_table_name ls
                            WHERE ls.ls_slugline = '$slugline'\n";
        $this->_db->exec();
        //$this->_db->showSQL();
        $out['data'] = $this->_db->fetch();
        return $out;
    }

    public function getAll() {
        $dbi = $this->_db->getTableName('lists_items');
        $this->_db->sql = "SELECT ls.*,
                                (SELECT COUNT(*) FROM $dbi WHERE li_ls_id = ls.ls_id) AS cnt,
                                CHAR_LENGTH(TRIM(ls_description)) AS len_descr,
                                CHAR_LENGTH(TRIM(ls_text)) AS len_text
                            FROM $this->_table_name ls
                            ORDER BY $this->_table_order ASC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function deleteByPk($id) {
        $this->updateByPk($id, array($this->_table_active => 0));
    }

}
