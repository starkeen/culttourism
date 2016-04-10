<?php

class MPhotos extends Model {

    protected $_table_pk = 'ph_id';
    protected $_table_order = 'ph_order';
    protected $_table_active = 'ph_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('photos');
        $this->_table_fields = array(
            'ph_link',
            'ph_title',
            'ph_author',
            'ph_src',
            'ph_width',
            'ph_height',
            'ph_lat',
            'ph_lon',
            'ph_date_add',
        );
        parent::__construct($db);
        $this->_addRelatedTable('pagecity');
    }

    public function getCityPagesWithoutPhotos() {
        $this->_db->sql = "SELECT * FROM {$this->_tables_related['pagecity']}
                            WHERE pc_coverphoto_id = 0
                            LIMIT 5";
        $this->_db->execute();
        return $this->_db->fetchAll();
    }

}
