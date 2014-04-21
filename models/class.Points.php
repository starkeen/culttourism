<?php

class Points extends Model {

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
    }

    public function getUnslug($limit = 10) {
        $out = array();
        return $out;
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
        parent::updateByPk($id, $values, $files);
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
        parent::insert($values, $files);
    }

    public function deleteByPk($id) {
        $this->updateByPk($id, array('pt_active' => 0));
    }

}
