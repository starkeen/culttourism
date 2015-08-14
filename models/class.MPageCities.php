<?php

class MPageCities extends Model {

    protected $_table_pk = 'pc_id';
    protected $_table_order = 'pc_id';
    protected $_table_active = 'pc_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('pagecity');
        $this->_table_fields = array(
            'pc_title',
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
            'pc_lastup_date',
            'pc_lastup_user',
            'pc_add_date',
            'pc_add_user',
            'pc_active',
        );
        parent::__construct($db);
        $this->_addRelatedTable('region_url');
    }

    public function getCityByUrl($url) {
        $dbcd = $this->_db->getTableName('city_data');
        $dbcf = $this->_db->getTableName('city_fields');

        $this->_db->sql = "SELECT *,
                                UNIX_TIMESTAMP(pc.pc_lastup_date) AS last_update,
                                CONCAT(uc.url, '/') AS url_canonical
                            FROM {$this->_tables_related['region_url']} url
                                LEFT JOIN $this->_table_name pc ON pc.pc_id = url.citypage
                                    LEFT JOIN {$this->_tables_related['region_url']} uc ON uc.uid = pc.pc_url_id
                            WHERE url.url = :xurl";

        $this->_db->execute(array(
            ':xurl' => trim($url),
        ));
        $out = $this->_db->fetch();

        $out['region_in'] = array();
        $out['region_near'] = array();
        $out['metas'] = array();

        //----------------------  в н у т р и  ------------------------
        if (!empty($out['pc_region_id']) && empty($out['pc_city_id'])) {
            $this->_db->sql = "SELECT pc.pc_title, url.url, pc.pc_inwheretext,
                                    UNIX_TIMESTAMP(pc.pc_add_date) AS last_update
                                FROM $this->_table_name pc
                                    LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = pc.pc_url_id
                                WHERE pc.pc_region_id = :pc_region_id
                                    AND pc.pc_city_id != 0
                                ORDER BY pc.pc_rank DESC, pc.pc_title";

            $this->_db->execute(array(
                ':pc_region_id' => $out['pc_region_id'],
            ));
            while ($subcity = $this->_db->fetch()) {
                $out['region_in'][] = array('title' => $subcity['pc_title'], 'url' => $subcity['url'], 'where' => $subcity['pc_inwheretext']);
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
            //$db->showSQL();

            $this->_db->execute(array(
                ':pc_title' => $out['pc_title'],
                ':pc_latitude' => $out['pc_latitude'],
                ':pc_longitude' => $out['pc_longitude'],
            ));
            while ($subcity = $this->_db->fetch()) {
                $out['region_near'][] = array(
                    'title' => $subcity['pc_title'],
                    'url' => $subcity['url'],
                    'where' => $subcity['pc_inwheretext'],
                );
                if ($subcity['last_update'] > $out['last_update']) {
                    $out['last_update'] = $subcity['last_update'];
                }
            }
        }

        //-----------------------  м е т а  -------------------------
        if (!empty($out['pc_id'])) {
            $this->_db->sql = "SELECT cf_title, cd_value
                        FROM $dbcd cd
                            LEFT JOIN $dbcf cf ON cf.cf_id = cd.cd_cf_id
                        WHERE cd.cd_pc_id = :pc_id
                            AND cd.cd_value != ''
                            AND cf.cf_active = 1
                        ORDER BY cf_order";

            $this->_db->execute(array(
                ':pc_id' => $out['pc_id'],
            ));
            $out['metas'] = $this->_db->fetchAll();
        }

        return $out;
    }

    public function getItemByPk($id) {
        $this->_db->sql = "SELECT t.*,
                                CONCAT(url.url, '/') AS url
                            FROM $this->_table_name t
                                LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = t.pc_url_id
                            WHERE $this->_table_pk = :cid";

        $this->_db->execute(array(
            ':cid' => intval($id),
        ));
        return $this->_db->fetch();
    }

    /**
     * Выбрать городав том же регионе
     * @param integer $cid
     * @return array
     */
    public function getCitiesSomeRegion($cid) {
        $this->_db->sql = "SELECT pc2.pc_id, pc2.pc_title, pc2.pc_latitude, pc2.pc_longitude,
                                CONCAT(ru.url, '/') AS url
                            FROM $this->_table_name pc
                                LEFT JOIN $this->_table_name pc2 ON pc2.pc_region_id = pc.pc_region_id AND pc2.pc_id != pc.pc_id
                                    LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc2.pc_url_id
                            WHERE pc.pc_id = :cid
                                AND pc2.pc_city_id != 0";

        $this->_db->execute(array(
            ':cid' => $cid,
        ));
        return $this->_db->fetchAll();
    }

    /**
     * Выбрать города в той же стране
     * @param integet $country_id
     * @return array
     */
    public function getCitiesSomeCountry($country_id) {
        $this->_db->sql = "SELECT pc2.pc_id, pc2.pc_title, pc2.pc_latitude, pc2.pc_longitude, CONCAT(ru.url, '/') AS url
                        FROM $this->_table_name pc2
                            LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc2.pc_url_id
                        WHERE pc2.pc_country_id = :pc_country_id
                            AND pc2.pc_city_id != 0";

        $this->_db->execute(array(
            ':pc_country_id' => $country_id,
        ));
        return $this->_db->fetchAll();
    }

    public function updateByPk($id, $values = array(), $files = array()) {
        if (isset($values['pc_latitude'])) {
            $values['pc_latitude'] = floatval(str_replace(',', '.', trim($values['pc_latitude'])));
            if ($values['pc_latitude'] == 0) {
                unset($values['pc_latitude']);
            }
        }
        if (isset($values['pc_longitude'])) {
            $values['pc_longitude'] = floatval(str_replace(',', '.', trim($values['pc_longitude'])));
            if ($values['pc_longitude'] == 0) {
                unset($values['pc_longitude']);
            }
        }
        parent::updateByPk($id, $values, $files);
    }

    public function insert($values = array(), $files = array()) {
        if (isset($values['pc_latitude'])) {
            $values['pc_latitude'] = floatval(str_replace(',', '.', trim($values['pc_latitude'])));
            if ($values['pc_latitude'] == 0) {
                unset($values['pc_latitude']);
            }
        }
        if (isset($values['pc_longitude'])) {
            $values['pc_longitude'] = floatval(str_replace(',', '.', trim($values['pc_longitude'])));
            if ($values['pc_longitude'] == 0) {
                unset($values['pc_longitude']);
            }
        }
        parent::insert($values, $files);
    }

    /*
     * Ищет подходящие страницы городов
     */

    public function getSuggestion($query) {
        $this->_db->sql = "SELECT pc_id, pc_title, url
                            FROM $this->_table_name pc
                                LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = pc.pc_url_id
                            WHERE pc.pc_title LIKE :name1 OR pc_title LIKE :name2
                                AND pc.pc_active = 1
                            GROUP BY pc.pc_title
                            ORDER BY pc.pc_title";

        $this->_db->execute(array(
            ':name1' => '%' . trim($query) . '%',
            ':name2' => '%' . trim(Helper::getQwerty($query)) . '%',
        ));
        return $this->_db->fetchAll();
    }

    /*
     * Заменяет все абсолютные ссылки относительными
     */

    public function repairLinksAbsRel() {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET pc_text = REPLACE(pc_text, '=\"http://" . _URL_ROOT . "/', '=\"/')";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name
                            SET pc_text = REPLACE(pc_text, '=\"https://" . _URL_ROOT . "/', '=\"/')";
        $this->_db->exec();
    }

    public function updatePagepath($id, $path) {
        $this->_db->sql = "UPDATE $this->_table_name SET pc_pagepath = :path
                            WHERE pc_id = :id AND pc_pagepath IS NULL";
        $this->_db->execute(array(
            ':id' => $id,
            ':path' => $path,
        ));
    }

    public function deleteByPk($id) {
        return $this->updateByPk($id, array('pc_active' => 0,));
    }

}
