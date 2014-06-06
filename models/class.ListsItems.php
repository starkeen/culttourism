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
        $dbru = $this->_db->getTableName('region_url');
        $this->_db->sql = "SELECT li.*, pt.*, pc.*,
                                UNIX_TIMESTAMP(pt.pt_lastup_date) AS last_update,
                                CONCAT(ru.url, '/') AS url_region,
                                CONCAT(ru.url, '/', pt.pt_slugline, '.html') AS url_canonical
                            FROM $this->_table_name li
                                LEFT JOIN $dbo pt ON pt.pt_id = li.li_pt_id
                                    LEFT JOIN $dbc pc ON pc.pc_id = pt.pt_citypage_id
                                        LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                            WHERE li_ls_id = '$this->_list_id'
                            GROUP BY pt.pt_id
                            ORDER BY $this->_table_order ASC, pt.pt_rank DESC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function getActive() {
        $dbo = $this->_db->getTableName('pagepoints');
        $dbc = $this->_db->getTableName('pagecity');
        $dbru = $this->_db->getTableName('region_url');
        $this->_db->sql = "SELECT li.*, pt.*, pc.*,
                                UNIX_TIMESTAMP(pt.pt_lastup_date) AS last_update,
                                CONCAT(ru.url, '/') AS url_region,
                                CONCAT(ru.url, '/', pt.pt_slugline, '.html') AS url_canonical
                            FROM $this->_table_name li
                                LEFT JOIN $dbo pt ON pt.pt_id = li.li_pt_id
                                    LEFT JOIN $dbc pc ON pc.pc_id = pt.pt_citypage_id
                                        LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                            WHERE li_ls_id = '$this->_list_id'
                                AND li.li_active = 1
                                AND pt.pt_active = 1
                            GROUP BY pt.pt_id
                            ORDER BY $this->_table_order ASC, pt.pt_rank DESC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function deleteByPk($id) {
        $this->updateByPk($id, array($this->_table_active => 0));
    }

}
