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
            'ph_pc_id',
            'ph_date_add',
            'ph_order',
            'ph_active',
        );
        parent::__construct($db);
        $this->_addRelatedTable('pagecity');
    }

    public function getPopularCitiesWithOnePhoto() {
        $this->_db->sql = "SELECT *
                            FROM {$this->_tables_related['pagecity']} pc
                            WHERE pc_count_photos = 1
                            ORDER BY pc_rank DESC, pc_id
                            LIMIT :limit";
        $this->_db->execute(array(
            ':limit' => (int) 20,
        ));
        return $this->_db->fetchAll();
    }

}
