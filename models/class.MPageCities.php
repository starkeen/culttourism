<?php

use app\utils\Keyboard;

class MPageCities extends Model
{
    protected $_table_pk = 'pc_id';
    protected $_table_order = 'pc_order';
    protected $_table_active = 'pc_active';

    public function __construct($db)
    {
        $this->_table_name = $db->getTableName('pagecity');
        $this->_table_fields = [
            'pc_title',
            'pc_title_unique',
            'pc_text',
            'pc_keywords',
            'pc_description',
            'pc_announcement',
            'pc_city_id',
            'pc_url_id',
            'pc_region_id',
            'pc_country_id',
            'pc_country_code',
            'pc_pagepath',
            'pc_latitude',
            'pc_longitude',
            'pc_latlon_zoom',
            'pc_osm_id',
            'pc_cnt_shows',
            'pc_rank',
            'pc_inwheretext',
            'pc_title_translit',
            'pc_title_english',
            'pc_title_synonym',
            'pc_website',
            'pc_coverphoto_id',
            'pc_count_points',
            'pc_count_metas',
            'pc_count_photos',
            'pc_lastup_date',
            'pc_lastup_user',
            'pc_add_date',
            'pc_add_user',
            'pc_order',
            'pc_active',
        ];
        parent::__construct($db);
        $this->addRelatedTable('region_url');
        $this->addRelatedTable('pagepoints');
        $this->addRelatedTable('city_data');
        $this->addRelatedTable('photos');
    }

    /**
     * Получение страницы по ее урлу
     *
     * @param string $url
     *
     * @return array
     */
    public function getCityByUrl(string $url): array
    {
        $this->_db->sql = "SELECT *,
                                UNIX_TIMESTAMP(pc.pc_lastup_date) AS last_update,
                                CONCAT(uc.url, '/') AS url_canonical
                            FROM {$this->_tables_related['region_url']} url
                                LEFT JOIN $this->_table_name pc ON pc.pc_id = url.citypage
                                    LEFT JOIN {$this->_tables_related['region_url']} uc ON uc.uid = pc.pc_url_id
                            WHERE url.url = :xurl";

        $this->_db->execute(
            [
                ':xurl' => trim($url),
            ]
        );
        $out = $this->_db->fetch();

        $out['region_in'] = [];
        $out['region_near'] = [];
        $out['metas'] = [];

        //----------------------  в н у т р и  ------------------------
        if (!empty($out['pc_region_id']) && empty($out['pc_city_id'])) {
            $this->_db->sql = "SELECT pc.pc_title, url.url, pc.pc_inwheretext,
                                    UNIX_TIMESTAMP(pc.pc_add_date) AS last_update
                                FROM $this->_table_name pc
                                    LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = pc.pc_url_id
                                WHERE pc.pc_region_id = :pc_region_id
                                    AND pc.pc_city_id != 0
                                ORDER BY pc.pc_rank DESC, pc.pc_title";

            $this->_db->execute(
                [
                    ':pc_region_id' => $out['pc_region_id'],
                ]
            );
            while ($subcity = $this->_db->fetch()) {
                $out['region_in'][] = [
                    'title' => $subcity['pc_title'],
                    'url' => $subcity['url'],
                    'where' => $subcity['pc_inwheretext'],
                ];
                if ($subcity['last_update'] > $out['last_update']) {
                    $out['last_update'] = $subcity['last_update'];
                }
            }
        }

        //----------------------  р я д о м  ------------------------
        if (!empty($out['pc_region_id']) && !empty($out['pc_city_id'])) {
            $this->_db->sql = "SELECT pc.pc_title, url.url, pc.pc_inwheretext,
                                    ROUND(1000 * (ABS(pc.pc_latitude - :pc_latitude) + ABS(pc.pc_longitude - :pc_longitude))) AS delta_sum,
                                    UNIX_TIMESTAMP(pc.pc_add_date) AS last_update
                                FROM $this->_table_name pc
                                    LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = pc.pc_url_id
                                WHERE pc.pc_city_id != 0
                                    AND pc.pc_title != :pc_title
                                    AND pc.pc_latitude > 0 AND pc.pc_longitude > 0
                                HAVING delta_sum < 5000
                                ORDER BY delta_sum
                                LIMIT 10";

            $this->_db->execute(
                [
                    ':pc_title' => $out['pc_title'],
                    ':pc_latitude' => $out['pc_latitude'],
                    ':pc_longitude' => $out['pc_longitude'],
                ]
            );
            while ($subcity = $this->_db->fetch()) {
                $out['region_near'][] = [
                    'title' => $subcity['pc_title'],
                    'url' => $subcity['url'],
                    'where' => $subcity['pc_inwheretext'],
                ];
                if ($subcity['last_update'] > $out['last_update']) {
                    $out['last_update'] = $subcity['last_update'];
                }
            }
        }

        //-----------------------  м е т а  -------------------------
        if (!empty($out['pc_id'])) {
            $cd = new MCityData($this->_db);
            $out['metas'] = $cd->getByCityId($out['pc_id']);
        }

        return $out;
    }

    /**
     * Получение страницы региона по $id
     *
     * @param integer $id
     *
     * @return array
     */
    public function getItemByPk($id): ?array
    {
        $this->_db->sql = "SELECT t.*,
                                UNIX_TIMESTAMP(t.pc_lastup_date) AS last_update,
                                CONCAT(url.url, '/') AS url
                            FROM $this->_table_name t
                                LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = t.pc_url_id
                            WHERE $this->_table_pk = :cid";

        $this->_db->execute(
            [
                ':cid' => $id,
            ]
        );

        return $this->_db->fetch();
    }

    /**
     * @return array
     */
    public function getActive(): array
    {
        $this->_db->sql = "
            SELECT t.*, ph.ph_src AS photo_src,
              CONCAT(url.url, '/') AS city_url,
              REPLACE(t.pc_text, '=\"/', CONCAT('=\"', :site_url1)) AS text_absolute
            FROM {$this->_table_name} t
              LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = t.pc_url_id
              LEFT JOIN {$this->_tables_related['photos']} ph ON ph.ph_id = t.pc_coverphoto_id
            WHERE {$this->_table_active} = 1
              AND LENGTH(pc_text) > 10
            ORDER BY {$this->_table_order} ASC
        ";
        $this->_db->execute(
            [
                ':site_url1' => GLOBAL_SITE_URL,
            ]
        );

        return $this->_db->fetchAll();
    }

    /**
     * Выбрать города в том же регионе
     *
     * @param int $cid
     *
     * @return array
     */
    public function getCitiesSomeRegion(int $cid): array
    {
        $this->_db->sql = "SELECT pc2.pc_id, pc2.pc_title, pc2.pc_latitude, pc2.pc_longitude,
                                CONCAT(ru.url, '/') AS url
                            FROM $this->_table_name pc
                                LEFT JOIN $this->_table_name pc2 ON pc2.pc_region_id = pc.pc_region_id AND pc2.pc_id != pc.pc_id
                                    LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc2.pc_url_id
                            WHERE pc.pc_id = :cid
                                AND pc2.pc_city_id != 0";

        $this->_db->execute(
            [
                ':cid' => $cid,
            ]
        );

        return $this->_db->fetchAll();
    }

    /**
     * Выбрать города в той же стране
     *
     * @param int $country_id
     *
     * @return array
     */
    public function getCitiesSomeCountry(int $country_id): array
    {
        $this->_db->sql = "SELECT pc2.pc_id, pc2.pc_title, pc2.pc_latitude, pc2.pc_longitude, CONCAT(ru.url, '/') AS url
                        FROM $this->_table_name pc2
                            LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc2.pc_url_id
                        WHERE pc2.pc_country_id = :pc_country_id
                            AND pc2.pc_city_id != 0";

        $this->_db->execute(
            [
                ':pc_country_id' => $country_id,
            ]
        );

        return $this->_db->fetchAll();
    }

    /**
     * Обновить данные по странице региона
     *
     * @param int $id
     * @param array $values
     * @param array $files
     *
     * @return int
     */
    public function updateByPk($id, $values = [], $files = [])
    {
        if (isset($values['pc_latitude'])) {
            $values['pc_latitude'] = (float) str_replace(',', '.', trim($values['pc_latitude']));
            if ($values['pc_latitude'] == 0) {
                unset($values['pc_latitude']);
            }
        }
        if (isset($values['pc_longitude'])) {
            $values['pc_longitude'] = (float) str_replace(',', '.', trim($values['pc_longitude']));
            if ($values['pc_longitude'] == 0) {
                unset($values['pc_longitude']);
            }
        }
        $values['pc_lastup_date'] = $this->now();
        return parent::updateByPk($id, $values, $files);
    }

    /**
     * Добавить новый регион
     *
     * @param array $values
     * @param array $files
     *
     * @return int
     */
    public function insert($values = [], $files = [])
    {
        if (isset($values['pc_latitude'])) {
            $values['pc_latitude'] = (float) str_replace(',', '.', trim($values['pc_latitude']));
            if ($values['pc_latitude'] == 0) {
                unset($values['pc_latitude']);
            }
        }
        if (isset($values['pc_longitude'])) {
            $values['pc_longitude'] = (float) str_replace(',', '.', trim($values['pc_longitude']));
            if ($values['pc_longitude'] == 0) {
                unset($values['pc_longitude']);
            }
        }
        if (empty($values['pc_title_unique'])) {
            $values['pc_title_unique'] = $values['pc_title'];
        }
        $values['pc_url_id'] = 0;
        $values['pc_add_date'] = $this->now();
        $values['pc_lastup_date'] = $this->now();
        $id = parent::insert($values, $files);

        $parent_variants = $this->searchPagesByFilter(
            [
                'pc_region_id' => $values['pc_region_id'],
                'pc_country_id' => $values['pc_country_id'],
                'pc_city_id' => 0,
            ]
        );
        $parent = $parent_variants[0] ?? ['url' => ''];
        $ru = new MRegionUrl($this->_db);
        $url_id = $ru->insert(
            [
                'url' => $parent['url'] . '/' . strtolower(str_replace(' ', '_', $values['pc_title_english'])),
                'citypage' => $id,
            ]
        );
        $this->updateByPk(
            $id,
            [
                'pc_url_id' => $url_id,
            ]
        );

        return $id;
    }

    /**
     * Подбираем страницы по жестким параметрам
     *
     * @param array $filter
     *
     * @return array
     */
    public function searchPagesByFilter(array $filter = []): array
    {
        $this->_db->sql = "SELECT *
                            FROM $this->_table_name pc
                                LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = pc.pc_url_id
                            WHERE pc.pc_active = 1\n";
        $binds = [];
        foreach ($filter as $k => $v) {
            $this->_db->sql .= "AND $k = :$k\n";
            $binds[':' . $k] = $v;
        }
        $this->_db->execute($binds);

        return $this->_db->fetchAll();
    }

    /**
     * Ищет подходящие страницы городов
     *
     * @param string $query
     *
     * @return array
     */
    public function getSuggestion(string $query): array
    {
        $this->_db->sql = "SELECT pc_id, pc_title_unique AS pc_title, url
                            FROM $this->_table_name pc
                                LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = pc.pc_url_id
                            WHERE pc.pc_title LIKE :name1 OR pc_title LIKE :name2
                                AND pc.pc_active = 1
                            GROUP BY pc.pc_title_unique";

        $this->_db->execute(
            [
                ':name1' => '%' . trim($query) . '%',
                ':name2' => '%' . trim(Keyboard::getQwerty($query)) . '%',
            ]
        );

        return $this->_db->fetchAll();
    }

    /**
     * Заменяет все абсолютные ссылки относительными
     */
    public function repairLinksAbsRel(): void
    {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET pc_text = REPLACE(pc_text, '=\"http://" . GLOBAL_URL_ROOT . "/', '=\"/')";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name
                            SET pc_text = REPLACE(pc_text, '=\"https://" . GLOBAL_URL_ROOT . "/', '=\"/')";
        $this->_db->exec();
    }

    /**
     * Крошки обновляем по странице региона
     *
     * @param int $id
     * @param string $path
     */
    public function updatePagepath(int $id, string $path): void
    {
        $this->_db->sql = "UPDATE $this->_table_name SET pc_pagepath = :path
                            WHERE pc_id = :id AND pc_pagepath IS NULL";
        $this->_db->execute(
            [
                ':id' => $id,
                ':path' => $path,
            ]
        );
    }

    /**
     * Обновление статистики по городам и регионам
     */
    public function updateStat(): void
    {
        $this->_db->sql = "UPDATE $this->_table_name pc
                            LEFT JOIN (SELECT pt.pt_citypage_id AS pc, COUNT(1) cnt
                                        FROM {$this->_tables_related['pagepoints']} pt
                                        WHERE pt.pt_active = 1
                                        GROUP BY pt.pt_citypage_id) AS stat
                                ON stat.pc = pc.pc_id
                            SET pc.pc_count_points = stat.cnt";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name pc
                            LEFT JOIN (SELECT cd.cd_pc_id AS pc, COUNT(1) cnt
                                        FROM {$this->_tables_related['city_data']} cd
                                        GROUP BY cd.cd_pc_id) AS stat
                                ON stat.pc = pc.pc_id
                            SET pc.pc_count_metas = stat.cnt";
        $this->_db->exec();
    }

    /**
     *
     */
    public function updateStatPhotos(): void
    {
        $this->_db->sql = "UPDATE $this->_table_name pc
                            LEFT JOIN (SELECT ph.ph_pc_id AS pc, COUNT(1) cnt
                                        FROM {$this->_tables_related['photos']} ph
                                        GROUP BY ph.ph_pc_id) AS stat
                                ON stat.pc = pc.pc_id
                            SET pc.pc_count_photos = stat.cnt";
        $this->_db->exec();
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    public function getCityPagesWithoutPhotos(int $limit = 5): array
    {
        $this->_db->sql = "SELECT *
                            FROM $this->_table_name pc
                            WHERE pc_coverphoto_id = 0
                            ORDER BY pc_rank DESC, pc_id
                            LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => $limit,
            ]
        );

        return $this->_db->fetchAll();
    }

    /**
     *
     * @param int $id
     *
     * @return int
     */
    public function deleteByPk($id)
    {
        return $this->updateByPk($id, ['pc_active' => 0,]);
    }
}
