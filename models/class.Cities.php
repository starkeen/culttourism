<?php

class Cities extends Model {

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

}
