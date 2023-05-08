<?php

use app\db\MyDB;

class MPhotos extends Model
{
    protected $_table_pk = 'ph_id';
    protected $_table_order = 'ph_order';
    protected $_table_active = 'ph_active';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('photos');
        $this->_table_fields = [
            'ph_link',
            'ph_title',
            'ph_author',
            'ph_src',
            'ph_width',
            'ph_weight',
            'ph_height',
            'ph_mime',
            'ph_lat',
            'ph_lon',
            'ph_pc_id',
            'ph_pt_id',
            'ph_date_add',
            'ph_order',
            'ph_active',
        ];
        parent::__construct($db);
        $this->addRelatedTable('pagecity');
        $this->addRelatedTable('pagepoints');
        $this->addRelatedTable('ref_pointtypes');
        $this->addRelatedTable('wordstat');
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
     *
     * @param int $count
     *
     * @return array[]
     */
    public function getPopularObjectsWithoutPhoto(int $count = 20): array
    {
        $this->_db->sql = "SELECT pt.*, pc.pc_id, pc.pc_title_unique
                            FROM {$this->_tables_related['pagepoints']} pt
                            LEFT JOIN {$this->_tables_related['ref_pointtypes']} pts ON pts.tp_id = pt.pt_type_id
                            LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pt.pt_citypage_id
                            WHERE (pt.pt_photo_id = 0 OR pt.pt_photo_id IS NULL)
                            AND pts.tr_sight = 1
                            AND pt.pt_deleted_at IS NULL
                            AND (pt.pt_type_id = 2 OR pt.pt_order IS NULL) -- временно работаем только с одним типом
                            ORDER BY pt.pt_is_best DESC, pt.pt_order ASC
                            LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => $count,
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

    public function getLegacyPhotos(int $count): array
    {
        $this->_db->sql = "SELECT *
                            FROM {$this->_table_name}
                            WHERE ph_src LIKE 'http%flickr.com%'
                                AND ph_active = 1
                            ORDER BY ph_id ASC
                            LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => $count,
            ]
        );
        return $this->_db->fetchAll();
    }

    public function updateObjectLink(int $oldPhotoId, int $newPhotoId): void
    {
        $this->_db->sql = "UPDATE {$this->_tables_related['pagepoints']}
                            SET pt_photo_id = :new_id
                            WHERE pt_photo_id = :old_id";
        $this->_db->execute(
            [
                ':old_id' => $oldPhotoId,
                ':new_id' => $newPhotoId,
            ]
        );
    }

    public function updateRegionLink(int $oldPhotoId, int $newPhotoId): void
    {
        $this->_db->sql = "UPDATE {$this->_tables_related['pagecity']}
                            SET pc_coverphoto_id = :new_id
                            WHERE pc_coverphoto_id = :old_id";
        $this->_db->execute(
            [
                ':old_id' => $oldPhotoId,
                ':new_id' => $newPhotoId,
            ]
        );
    }
}
