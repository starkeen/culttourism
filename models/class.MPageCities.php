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
            'pc_city_id',
            'pc_url_id',
            'pc_region_id',
            'pc_country_id',
            'pc_pagepath',
            'pc_latitude',
            'pc_longitude',
            'pc_latlon_zoom',
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
        $dbu = $this->_db->getTableName('region_url');
        $dbcd = $this->_db->getTableName('city_data');
        $dbcf = $this->_db->getTableName('city_fields');

        $xurl = $this->_db->getEscapedString($url);

        $out = array();

        $this->_db->sql = "SELECT *,
                                UNIX_TIMESTAMP(pc.pc_lastup_date) AS last_update,
                                CONCAT(uc.url, '/') AS url_canonical
                            FROM $dbu url
                                LEFT JOIN $this->_table_name pc ON pc.pc_id = url.citypage
                                    LEFT JOIN $dbu uc ON uc.uid = pc.pc_url_id
                            WHERE url.url = '$xurl'";
        $this->_db->exec();
        $out = $this->_db->fetch();

        $out['region_in'] = array();
        $out['region_near'] = array();
        $out['metas'] = array();

        //----------------------  в н у т р и  ------------------------
        if ($out['pc_region_id'] > 0 && $out['pc_city_id'] == 0) {
            $this->_db->sql = "SELECT pc.pc_title, url.url, pc.pc_inwheretext,
                                    UNIX_TIMESTAMP(pc.pc_add_date) AS last_update
                                FROM $this->_table_name pc
                                    LEFT JOIN $dbu url ON url.uid = pc.pc_url_id
                                WHERE pc.pc_region_id = '{$out['pc_region_id']}'
                                    AND pc.pc_city_id != 0
                                ORDER BY pc.pc_rank DESC, pc.pc_title";
            $this->_db->exec();
            while ($subcity = $this->_db->fetch()) {
                $out['region_in'][] = array('title' => $subcity['pc_title'], 'url' => $subcity['url'], 'where' => $subcity['pc_inwheretext']);
                if ($subcity['last_update'] > $out['last_update']) {
                    $out['last_update'] = $subcity['last_update'];
                }
            }
        }

        //----------------------  р я д о м  ------------------------
        if ($out['pc_region_id'] > 0 && $out['pc_city_id'] > 0) {
            $this->_db->sql = "SELECT pc.pc_title, url.url, pc.pc_inwheretext,
                                    ROUND(1000 * (ABS(pc.pc_latitude - {$out['pc_latitude']}) + ABS(pc.pc_longitude - {$out['pc_longitude']}))) AS delta_sum,
                                    UNIX_TIMESTAMP(pc.pc_add_date) AS last_update
                                FROM $this->_table_name pc
                                    LEFT JOIN $dbu url ON url.uid = pc.pc_url_id
                                WHERE pc.pc_city_id != 0
                                    AND pc.pc_title != '{$out['pc_title']}'
                                    AND pc.pc_latitude > 0 AND pc.pc_longitude > 0
                                HAVING delta_sum < 5000
                                ORDER BY delta_sum
                                LIMIT 10";
            //$db->showSQL();
            $this->_db->exec();
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
        $this->_db->sql = "SELECT cf_title, cd_value
                        FROM $dbcd cd
                            LEFT JOIN $dbcf cf ON cf.cf_id = cd.cd_cf_id
                        WHERE cd.cd_pc_id = '{$out['pc_id']}'
                            AND cd.cd_value != ''
                            AND cf.cf_active = 1
                        ORDER BY cf_order";
        $this->_db->exec();
        $out['metas'] = $this->_db->fetchAll();

        return $out;
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

    public function deleteByPk($id) {
        $this->updateByPk($id, array('pc_active' => 0,));
    }

    /*
     * Ищет подходящие страницы городов
     */

    public function getSuggestion($query) {
        $name1 = $this->escape($query);
        $name2 = $this->escape(Helper::getQwerty($query));
        $this->_db->sql = "SELECT pc_id, pc_title, url
                            FROM $this->_table_name pc
                                LEFT JOIN {$this->_tables_related['region_url']} url ON url.uid = pc.pc_url_id
                            WHERE pc.pc_title LIKE '%$name1%' OR pc_title LIKE '%$name2%'
                                AND pc.pc_active = 1
                            ORDER BY pc.pc_title";
        $this->_db->exec();
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

}
