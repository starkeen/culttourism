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
        $dbpc = $this->_db->getTableName('pagecity');
        $dbrt = $this->_db->getTableName('ref_pointtypes');
        $this->_db->sql = "SELECT pt_id, pt_name, pc_title, pc_title_english, tr_sight
                            FROM $this->_table_name pt
                                LEFT JOIN $dbpc pc ON pc.pc_id = pt.pt_citypage_id
                                LEFT JOIN $dbrt rt ON rt.tp_id = pt.pt_type_id
                            WHERE pt.pt_slugline = ''
                            ORDER BY pt.pt_rank DESC
                            LIMIT $limit";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function searchSlugline($slugline) {
        $this->_db->sql = "SELECT *
                            FROM $this->_table_name pt
                            WHERE TRIM(pt.pt_slugline) = TRIM('$slugline')
                            ORDER BY pt.pt_rank DESC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function searchByName($name) {
        $this->_db->sql = "SELECT *
                            FROM $this->_table_name pt
                            WHERE TRIM(pt.pt_name) = TRIM('$name')
                            ORDER BY pt.pt_rank DESC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function createSluglineById($id) {
        $point = $this->getItemByPk($id);

        $name = trim($point['pt_name']);

        $analogs = $this->searchByName($point['pt_name']);
        if ($point['tr_sight'] == 0 || count($analogs) > 1) {
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
        $id = intval($id);
        $dbpc = $this->_db->getTableName('pagecity');
        $dbrt = $this->_db->getTableName('ref_pointtypes');
        $this->_db->sql = "SELECT *
                            FROM $this->_table_name pt
                                LEFT JOIN $dbpc pc ON pc.pc_id = pt.pt_citypage_id
                                LEFT JOIN $dbrt rt ON rt.tp_id = pt.pt_type_id
                            WHERE $this->_table_pk = '$id'";
        $this->_db->exec();
        return $this->_db->fetch();
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
