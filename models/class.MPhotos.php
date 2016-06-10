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
        $this->_addRelatedTable('wordstat');
    }

    /**
     * Список городов с одной (автоматической) фоткой
     * @return array
     */
    public function getPopularCitiesWithOnePhoto() {
        $this->_db->sql = "SELECT pc.*, ws.ws_weight, ws.ws_weight_min, ws.ws_weight_max
                            FROM {$this->_tables_related['pagecity']} pc
                                LEFT JOIN {$this->_tables_related['wordstat']} ws
                                    ON ws.ws_city_id = pc.pc_city_id AND ws.ws_city_title = pc.pc_title
                            WHERE pc_count_photos = 1
                            ORDER BY ws.ws_weight_min DESC, pc_rank DESC, pc_id
                            LIMIT :limit";
        $this->_db->execute(array(
            ':limit' => (int) 20,
        ));
        return $this->_db->fetchAll();
    }

    /**
     * 
     * @param array $filter
     * @return array
     */
    public function getItemsByFilter($filter) {
        $filter['join'][] = 'LEFT JOIN ' . $this->_tables_related['pagecity'] . ' pc ON pc.pc_id = t.ph_pc_id';
        return parent::getItemsByFilter($filter);
    }

}
