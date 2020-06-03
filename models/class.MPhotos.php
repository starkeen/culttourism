<?php

class MPhotos extends Model
{
    protected $_table_pk = 'ph_id';
    protected $_table_order = 'ph_order';
    protected $_table_active = 'ph_active';

    public function __construct($db)
    {
        $this->_table_name = $db->getTableName('photos');
        $this->_table_fields = [
            'ph_link',
            'ph_title',
            'ph_author',
            'ph_src',
            'ph_width',
            'ph_height',
            'ph_lat',
            'ph_lon',
            'ph_pc_id',
            'ph_pt_id',
            'ph_date_add',
            'ph_order',
            'ph_active',
        ];
        parent::__construct($db);
        $this->_addRelatedTable('pagecity');
        $this->_addRelatedTable('pagepoints');
        $this->_addRelatedTable('wordstat');
    }

    /**
     * Список городов с одной (автоматической) фоткой
     * @return array
     */
    public function getPopularCitiesWithOnePhoto(): array
    {
        $this->_db->sql = "SELECT pc.*, ws.ws_weight, ws.ws_weight_min, ws.ws_weight_max
                            FROM {$this->_tables_related['pagecity']} pc
                                LEFT JOIN {$this->_tables_related['wordstat']} ws
                                    ON ws.ws_city_id = pc.pc_city_id AND ws.ws_city_title = pc.pc_title
                            WHERE pc_count_photos = 1
                            ORDER BY ws.ws_weight_min DESC, pc_rank DESC, pc_id
                            LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => 20,
            ]
        );
        return $this->_db->fetchAll();
    }

    /**
     * Список популярных точек без фотографий
     * @return array
     */
    public function getPopularObjectsWithoutPhoto(): array
    {
        $this->_db->sql = "SELECT pt.*, pc.pc_id, pc.pc_title_unique
                            FROM {$this->_tables_related['pagepoints']} pt
                            LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pt.pt_citypage_id
                            WHERE pt.pt_photo_id = 0
                            ORDER BY pt.pt_rank DESC
                            LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => 20,
            ]
        );
        return $this->_db->fetchAll();
    }

    /**
     * Все фотографии региона
     *
     * @param int $pcid
     *
     * @return array
     */
    public function getItemsByRegion(int $pcid): array
    {
        $filter = [];
        $filter['where'][] = 'ph_pc_id = ' . $pcid;
        return $this->getItemsByFilter($filter);
    }

    /**
     *
     * @param array $filter
     *
     * @return array
     */
    public function getItemsByFilter($filter): array
    {
        $filter['join'][] = 'LEFT JOIN ' . $this->_tables_related['pagecity'] . ' pc ON pc.pc_id = t.ph_pc_id';
        $filter['join'][] = 'LEFT JOIN ' . $this->_tables_related['pagepoints'] . ' pt ON pt.pt_id = t.ph_pt_id';
        $filter['order'] = 'ph_date_add DESC';
        return parent::getItemsByFilter($filter);
    }
}
