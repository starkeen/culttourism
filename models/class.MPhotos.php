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
    }

}
