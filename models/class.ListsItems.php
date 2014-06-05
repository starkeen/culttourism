<?php

class ListsItems extends Model {

    protected $_table_pk = 'li_id';
    protected $_table_order = 'li_order';
    protected $_table_active = 'li_active';
    private $_list_id = 0;

    public function __construct($db, $lid = 0) {
        $this->_table_name = $db->getTableName('lists_items');
        $this->_table_fields = array(
            'li_ls_id',
            'li_pt_id',
            'li_order',
            'li_active',
        );
        $this->_list_id = intval($lid);
        parent::__construct($db);
    }

    public function getAll() {
        $dbo = $this->_db->getTableName('pagepoints');
        $dbc = $this->_db->getTableName('pagecity');
        $this->_db->sql = "SELECT *
                            FROM $this->_table_name li
                                LEFT JOIN $dbo pt ON pt.pt_id = li.li_pt_id
                                    LEFT JOIN $dbc pc ON pc.pc_id = pt.pt_citypage_id
                            WHERE li_ls_id = '$this->_list_id'\n";
        if ($this->_table_order) {
            $this->_db->sql .= "ORDER BY $this->_table_order ASC\n";
        }
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function deleteByPk($id) {
        $this->updateByPk($id, array($this->_table_active => 0));
    }

}
