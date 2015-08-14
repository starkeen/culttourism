<?php

class MPagePoints extends Model {

    protected $_table_pk = 'pt_id';
    protected $_table_order = 'pt_id';
    protected $_table_active = 'pt_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('pagepoints');
        $this->_table_fields = array(
            'pt_name',
            'pt_description',
            'pt_slugline',
            'pt_latitude',
            'pt_longitude',
            'pt_latlon_zoom',
            'pt_type_id',
            'pt_create_date',
            'pt_create_user',
            'pt_lastup_date',
            'pt_lastup_user',
            'pt_city_id',
            'pt_region_id',
            'pt_country_id',
            'pt_citypage_id',
            'pt_cnt_shows',
            'pt_rank',
            'pt_website',
            'pt_worktime',
            'pt_adress',
            'pt_phone',
            'pt_email',
            'pt_is_best',
            'pt_active',
        );
        parent::__construct($db);
        $this->_addRelatedTable('pagecity');
        $this->_addRelatedTable('region_url');
        $this->_addRelatedTable('lists');
        $this->_addRelatedTable('lists_items');
        $this->_addRelatedTable('data_check');
        $this->_addRelatedTable('ref_pointtypes');
    }

    /**
     * Все точки региона, сгруппированные по признаку достопримечательности
     * @param integer $city_id
     * @param bool $show_all
     * @return array
     */
    public function getPointsByCity($city_id, $show_all = false) {
        $out = array(
            'points' => array(),
            'points_sight' => array(),
            'points_service' => array(),
            'types' => array(
                0 => array(),
                1 => array(),
            ),
            'last_update' => 0,
        );
        $this->_db->sql = "SELECT *,
                                CONCAT(pt.pt_slugline, '.html') AS url_canonical,
                                UNIX_TIMESTAMP(pt.pt_lastup_date) AS last_update
                            FROM $this->_table_name pt
                                LEFT JOIN {$this->_tables_related['ref_pointtypes']} rt ON rt.tp_id = pt.pt_type_id
                            WHERE pt.pt_citypage_id = :city_id\n";
        if (!$show_all) {
            $this->_db->sql .= "AND pt.pt_active = 1\n";
        }
        $this->_db->sql .= "ORDER BY pt.pt_active DESC, rt.tr_sight desc, pt.pt_rank desc, rt.tr_order, pt.pt_name";

        $this->_db->execute(array(
            ':city_id' => $city_id,
        ));
        while ($point = $this->_db->fetch()) {
            $out['types'][$point['tr_sight']][$point['pt_type_id']] = array(
                'short' => $point['tp_short'],
                'full' => $point['tp_name'],
                'icon' => $point['tp_icon'],
            );

            $short_lenght = 300;
            $point['short'] = html_entity_decode(strip_tags($point['pt_description']), ENT_QUOTES, 'utf-8');
            $short_end = @mb_strpos($point['short'], '.', $short_lenght, 'utf-8');
            if (mb_strlen($point['short']) >= $short_lenght && $short_end) {
                $point['short'] = mb_substr($point['short'], 0, $short_end, 'utf-8') . '&hellip;';
            }

            $point['pt_name'] = htmlentities($point['pt_name'], ENT_QUOTES, 'UTF-8', false);

            $point_lat = $point['pt_latitude'];
            $point_lon = $point['pt_longitude'];
            if ($point_lat && $point_lon) {
                $point_lat_short = mb_substr($point_lat, 0, 8);
                $point_lon_short = mb_substr($point_lon, 0, 8);
                if ($point_lat >= 0) {
                    $point_lat_w = "N$point_lat_short";
                } else {
                    $point_lat_w = "S$point_lat_short";
                }
                if ($point_lon >= 0) {
                    $point_lon_w = "E$point_lon_short";
                } else {
                    $point_lon_w = "W$point_lon_short";
                }
                $point['gps_dec'] = "$point_lat_w $point_lon_w";
            } else {
                $point['gps_dec'] = null;
            }

            $out['points'][] = $point;
            if ($point['tr_sight'] == 1) {
                $out['points_sight'][] = $point;
            } else {
                $out['points_service'][] = $point;
            }
            if ($point['last_update'] > $out['last_update']) {
                $out['last_update'] = $point['last_update'];
            }
        }
        return $out;
    }

    public function getPointsByBounds($bounds, $selected_object_id = 0) {
        $this->_db->sql = "SELECT pp.*,
                                IF (pp.pt_id = :selected_object_id2, 1, 0) AS obj_selected,
                                CONCAT(:url_root1, ru.url, '/') AS cityurl,
                                CONCAT(:url_root2, ru.url, '/', pp.pt_slugline, '.html') AS objurl,
                                CONCAT(ru.url, '/', pp.pt_slugline, '.html') AS objuri
                            FROM $this->_table_name AS pp
                                LEFT JOIN {$this->_tables_related['ref_pointtypes']} pt ON pt.tp_id = pp.pt_type_id
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pp.pt_citypage_id
                                    LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc.pc_url_id
                            WHERE pp.pt_active = 1
                                AND pp.pt_latitude BETWEEN :bounds_min_lat AND :bounds_max_lat
                                AND pp.pt_longitude BETWEEN :bounds_min_lon AND :bounds_max_lon
                                OR pp.pt_id = :selected_object_id1
                            ORDER BY pt.tr_order DESC, pp.pt_rank
                            LIMIT 300";

        $this->_db->execute(array(
            ':url_root1' => _URL_ROOT,
            ':url_root2' => _URL_ROOT,
            ':selected_object_id1' => $selected_object_id,
            ':selected_object_id2' => $selected_object_id,
            ':bounds_min_lat' => $bounds['min_lat'],
            ':bounds_max_lat' => $bounds['max_lat'],
            ':bounds_min_lon' => $bounds['min_lon'],
            ':bounds_max_lon' => $bounds['max_lon'],
        ));
        return $this->_db->fetchAll();
    }

    public function getUnslug($limit = 10) {
        $this->_db->sql = "SELECT pt_id, pt_name, pc_title, pc_title_english, tr_sight
                            FROM $this->_table_name pt
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pt.pt_citypage_id
                                LEFT JOIN {$this->_tables_related['ref_pointtypes']} rt ON rt.tp_id = pt.pt_type_id
                            WHERE pt.pt_slugline = ''
                            ORDER BY pt.pt_rank DESC
                            LIMIT $limit";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function searchSlugline($slugline) {
        $this->_db->sql = "SELECT *,
                                '' AS gps_dec,
                                UNIX_TIMESTAMP(pt.pt_lastup_date) AS last_update,
                                CONCAT(ru.url, '/', pt.pt_slugline, '.html') AS url_canonical
                            FROM $this->_table_name pt
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pt.pt_citypage_id
                                    LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc.pc_url_id
                                LEFT JOIN {$this->_tables_related['ref_pointtypes']} rt ON rt.tp_id = pt.pt_type_id
                            WHERE pt.pt_slugline = :slugline
                            ORDER BY pt.pt_rank DESC";

        $this->_db->execute(array(
            ':slugline' => trim($slugline),
        ));
        return $this->_db->fetchAll();
    }

    public function searchByName($name, $like = false) {
        $this->_db->sql = "SELECT *
                            FROM $this->_table_name pt\n";
        if ($like) {
            $this->_db->sql .= "WHERE pt.pt_name LIKE '%$name%'\n";
        } else {
            $this->_db->sql .= "WHERE TRIM(pt.pt_name) = TRIM('$name')\n";
        }
        $this->_db->sql .= "ORDER BY pt.pt_rank DESC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function createSluglineById($id) {
        $point = $this->getItemByPk($id);

        $name = trim($point['pt_name']);

        $analogs = $this->searchByName($point['pt_name']);
        if (($point['tr_sight'] == 0 && $point['pt_type_id'] != 0) || count($analogs) > 1) {
            $name = $point['pc_title'] . ' ' . $name;
        }
        $name_url = trim(preg_replace('/[^a-z0-9-_]+/', '', strtolower(Helper::getTranslit($name, '_'))), '_-');

        $concurents = $this->searchSlugline($name_url);
        if (count($concurents) > 0) {
            $name_url = trim(trim(strtolower(trim($point['pc_title_english'])) . '_' . $name_url), '_-');
        }

        $concurents_else = $this->searchSlugline($name_url);
        if (count($concurents_else) > 0) {
            $name_url = trim($name_url . '_' . count($concurents_else), '_-');
        }

        $concurents_last = $this->searchSlugline($name_url);
        if (count($concurents_last) > 0) {
            $name_url = trim($name_url . '_' . $point['pt_id'], '_-');
        }

        $this->updateByPk($point['pt_id'], array('pt_slugline' => $name_url));
    }

    public function checkSluglines() {
        $out = array(
            'state' => true,
            'errors' => array(),
            'doubles' => array(),
        );
        $this->_db->sql = "SELECT pt_id, pt_name, pt.pt_slugline, count(*) AS cnt
                            FROM $this->_table_name pt
                            WHERE pt.pt_slugline != ''
                            GROUP BY pt.pt_slugline
                            HAVING cnt > 1
                            ORDER BY pt.pt_rank DESC";
        $this->_db->exec();
        $out['doubles'] = $this->_db->fetchAll();
        if (count($out['doubles']) > 0) {
            $out['state'] = false;
            foreach ($out['doubles'] as $i => $double) {
                $out['doubles'][$i]['objects'] = $this->searchSlugline($double['pt_slugline']);
                foreach ($out['doubles'][$i]['objects'] as $obj) {
                    $out['errors'][] = "Дублирование Slugline '{$double['pt_slugline']}': {$obj['pt_id']} / {$obj['pt_name']}";
                }
            }
        }
        return $out;
    }

    public function getItemByPk($id) {
        $this->_db->sql = "SELECT *,
                                '' AS gps_dec,
                                CONCAT(ru.url, '/', pt.pt_slugline, '.html') AS url_canonical,
                                UNIX_TIMESTAMP(pt.pt_lastup_date) AS last_update
                            FROM $this->_table_name pt
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pt.pt_citypage_id
                                    LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc.pc_url_id
                                LEFT JOIN {$this->_tables_related['ref_pointtypes']} rt ON rt.tp_id = pt.pt_type_id
                            WHERE $this->_table_pk = :oid";

        $this->_db->execute(array(
            ':oid' => intval($id),
        ));
        return $this->_db->fetch();
    }

    public function getGeoPointsByCityId($cid) {
        $this->_db->sql = "SELECT pp.*,
                                CONCAT(:url_root1, ru.url, '/') AS cityurl,
                                CONCAT(:url_root2, ru.url, '/', pp.pt_slugline, '.html') AS objurl
                            FROM $this->_table_name AS pp
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pp.pt_citypage_id
                                LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc.pc_url_id
                            WHERE pt_citypage_id = :cid
                                AND pt_latitude != ''
                                AND pt_longitude != ''
                                AND pt_active = 1";

        $this->_db->execute(array(
            ':cid' => $cid,
            ':url_root1' => _URL_ROOT,
            ':url_root2' => _URL_ROOT,
        ));
        return $this->_db->fetchAll();
    }

    public function getPointsWithoutAddrs($limit = 100) {
        $this->_db->sql = "SELECT pt.pt_id, pt.pt_name, pt.pt_adress,
                                pt.pt_latitude, pt.pt_longitude,
                                pc.pc_title, pc.pc_latitude, pc.pc_longitude
                            FROM $this->_table_name pt
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pt.pt_citypage_id
                                LEFT JOIN {$this->_tables_related['data_check']} dc ON dc.dc_item_id = pt.pt_id
                                    AND dc.dc_type = 'point'
                                    AND dc.dc_field = 'pt_adress'
                            WHERE pt.pt_active = 1
                                AND ABS(CHAR_LENGTH(pt.pt_adress)-CHAR_LENGTH(pc.pc_title)) < 6
                                AND pt.pt_latitude IS NOT NULL
                            ORDER BY dc.dc_date
                            LIMIT :limit";
        $this->_db->execute(array(
            ':limit' => $limit,
        ));
        return $this->_db->fetchAll();
    }

    public function updateByPk($id, $values = array(), $files = array()) {
        if (isset($values['pt_latitude'])) {
            $values['pt_latitude'] = floatval(str_replace(',', '.', trim($values['pt_latitude'])));
            if ($values['pt_latitude'] == 0) {
                unset($values['pt_latitude']);
            }
        }
        if (isset($values['pt_longitude'])) {
            $values['pt_longitude'] = floatval(str_replace(',', '.', trim($values['pt_longitude'])));
            if ($values['pt_longitude'] == 0) {
                unset($values['pt_longitude']);
            }
        }
        if (isset($values['pt_website']) && strlen($values['pt_website']) != 0 && strpos($values['pt_website'], 'http') === false) {
            $values['pt_website'] = 'http://' . $values['pt_website'];
        }
        $values['pt_lastup_date'] = $this->now();
        $values['pt_lastup_user'] = $this->getUserId();
        return parent::updateByPk($id, $values, $files);
    }

    public function insert($values = array(), $files = array()) {
        if (isset($values['pt_latitude'])) {
            $values['pt_latitude'] = floatval(str_replace(',', '.', trim($values['pt_latitude'])));
            if ($values['pt_latitude'] == 0) {
                unset($values['pt_latitude']);
            }
        }
        if (isset($values['pt_longitude'])) {
            $values['pt_longitude'] = floatval(str_replace(',', '.', trim($values['pt_longitude'])));
            if ($values['pt_longitude'] == 0) {
                unset($values['pt_longitude']);
            }
        }
        if (!isset($values['pt_create_date'])) {
            $values['pt_create_date'] = $this->now();
        }
        if (!isset($values['pt_create_user'])) {
            $values['pt_create_user'] = $this->getUserId();
        }
        $values['pt_lastup_date'] = $values['pt_create_date'];
        $values['pt_lastup_user'] = $values['pt_create_user'];
        if (isset($values['pt_website']) && strlen($values['pt_website']) != 0 && strpos($values['pt_website'], 'http') === false) {
            $values['pt_website'] = 'http://' . $values['pt_website'];
        }
        $new_id = parent::insert($values, $files);
        $this->createSluglineById($new_id);
        return $new_id;
    }

    public function deleteByPk($id) {
        return $this->updateByPk($id, array($this->_table_active => 0));
    }

    /*
     * Заменяет все абсолютные ссылки относительными
     */

    public function repairLinksAbsRel() {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET pt_description = REPLACE(pt_description, '=\"http://" . _URL_ROOT . "/', '=\"/')";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name
                            SET pt_description = REPLACE(pt_description, '=\"https://" . _URL_ROOT . "/', '=\"/')";
        $this->_db->exec();
    }

}
