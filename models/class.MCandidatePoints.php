<?php

class MCandidatePoints extends Model {

    protected $_table_pk = 'cp_id';
    protected $_table_order = 'cp_date';
    protected $_table_active = 'cp_active';
    
    private $_types_markers = array(
        1 => array('памятник',), //памятники
        2 => array('цирт', 'зоопарк',), //места
        3 => array('церковь', 'храм', 'монастырь',), //церкви
        4 => array('музей', 'галерея',), //музеи
        5 => array('парк',), //парки
        6 => array('усадьба',), //усадьбы
        7 => array('вокзал',), //вокзалы
        8 => array('кафе', 'ресторан',), //кафе
        9 => array('гостиница', 'отель', 'хостел',), //гостиницы
    );

    public function __construct($db) {
        $this->_table_name = $db->getTableName('candidate_points');
        $this->_table_fields = array(
            'cp_date',
            'cp_title',
            'cp_text',
            'cp_city',
            'cp_addr',
            'cp_phone',
            'cp_web',
            'cp_worktime',
            'cp_email',
            'cp_sender',
            'cp_referer',
            'cp_type_id',
            'cp_citypage_id',
            'cp_latitude',
            'cp_longitude',
            'cp_zoom',
            'cp_source_id',
            'cp_point_id',
            'cp_state',
            'cp_active',
        );
        parent::__construct($db);
        $this->_addRelatedTable('pagecity');
        $this->_addRelatedTable('uniref_values');
        $this->_addRelatedTable('ref_pointtypes');
    }

    public function add($data) {
        $data['cp_date'] = $this->now();
        $data['cp_state'] = 3;
        $data['cp_active'] = 1;
        if ($data['cp_type_id'] == 0) {
            foreach($this->_types_markers as $type => $markers) {
                foreach($markers as $marker) {
                    if (strpos($data['cp_state'], $marker) !== false) {
                        $data['cp_type_id'] = $type;
                    }
                }
            }
        }
        return $this->insert($data);
    }

    public function getActive() {
        $this->_db->sql = "SELECT t.*,
                                pc.pc_title AS page_title,
                                uv_stat.uv_title AS state_title,
                                pt.tp_icon AS type_icon, pt.tp_short AS type_title
                            FROM $this->_table_name AS t
                                LEFT JOIN {$this->_tables_related['pagecity']} AS pc
                                    ON pc.pc_id = t.cp_citypage_id
                                LEFT JOIN {$this->_tables_related['uniref_values']} AS uv_stat
                                    ON uv_stat.uv_id = t.cp_state
                                LEFT JOIN {$this->_tables_related['ref_pointtypes']} AS pt
                                    ON pt.tp_id = t.cp_type_id
                            WHERE $this->_table_active = 1
                            ORDER BY $this->_table_order ASC\n";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

}
