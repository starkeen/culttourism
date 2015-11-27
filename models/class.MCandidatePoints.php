<?php

class MCandidatePoints extends Model {

    protected $_table_pk = 'cp_id';
    protected $_table_order = 'cp_date';
    protected $_table_active = 'cp_active';

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
        $this->_addRelatedTable('region_url');

        $rpt = new MRefPointtypes($this->_db);
        $this->types_markers = $rpt->getMarkers();
    }

    public function add($data) {
        $data['cp_date'] = $this->now();
        $data['cp_state'] = 3;
        $data['cp_active'] = 1;
        if (isset($data['cp_type_id']) && $data['cp_type_id'] == 0) {
            foreach ($this->types_markers as $type => $markers) {
                foreach ($markers as $marker) {
                    if (mb_stripos($data['cp_title'], $marker, 0, 'utf-8') !== false) {
                        $data['cp_type_id'] = $type;
                    }
                }
            }
        }
        if (strlen($data['cp_web']) != 0 && strpos($data['cp_web'], 'http') === false) {
            $data['cp_web'] = 'http://' . $data['cp_web'];
        }
        return $this->insert($data);
    }

    public function getByFilter($filter) {
        $this->_db->sql = "SELECT t.*,
                                pc.pc_title AS page_title, CONCAT(u.url, '/') AS page_url,
                                uv_stat.uv_title AS state_title,
                                pt.tp_icon AS type_icon, pt.tp_short AS type_title,
                                 CHAR_LENGTH(cp_text) AS text_len
                            FROM $this->_table_name AS t
                                LEFT JOIN {$this->_tables_related['pagecity']} AS pc
                                    ON pc.pc_id = t.cp_citypage_id
                                    LEFT JOIN {$this->_tables_related['region_url']} AS u
                                        ON u.uid = pc.pc_url_id
                                LEFT JOIN {$this->_tables_related['uniref_values']} AS uv_stat
                                    ON uv_stat.uv_id = t.cp_state
                                LEFT JOIN {$this->_tables_related['ref_pointtypes']} AS pt
                                    ON pt.tp_id = t.cp_type_id
                            WHERE $this->_table_active = 1\n";
        if ($filter['type'] > 0) {
            $this->_db->sql .= "AND t.cp_type_id = '" . intval($filter['type']) . "'\n";
        }
        if ($filter['type'] == -1) {
            $this->_db->sql .= "AND t.cp_type_id = '0'\n";
        }
        if ($filter['pcid'] > 0) {
            $this->_db->sql .= "AND t.cp_citypage_id = '" . intval($filter['pcid']) . "'\n";
        }
        if ($filter['state'] != 0) {
            $this->_db->sql .= "AND t.cp_state = '" . intval($filter['state']) . "'\n";
        }
        $this->_db->sql .= "ORDER BY $this->_table_order ASC\n";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    /**
     * Статистика по набору активных заявок
     */
    public function getMatrix() {
        $this->_db->sql = "SELECT count(1) AS cnt,
                                pc.pc_id, pc.pc_title,
                                IFNULL(pt.tp_id, -1) AS tp_id, pt.tp_name, pt.tp_icon
                            FROM $this->_table_name AS t
                                LEFT JOIN {$this->_tables_related['pagecity']} AS pc
                                    ON pc.pc_id = t.cp_citypage_id
                                LEFT JOIN {$this->_tables_related['ref_pointtypes']} AS pt
                                    ON pt.tp_id = t.cp_type_id
                            WHERE $this->_table_active = 1
                            GROUP BY pc.pc_id, pt.tp_id
                            ORDER BY cnt, pc.pc_title, pt.tr_order";
        $this->_db->exec();
        $out = array(
            'types' => array(),
            'counts' => array(),
        );
        while ($row = $this->_db->fetch()) {
            $out['types'][$row['tp_id']] = array(
                'title' => $row['tp_name'],
                'icon' => $row['tp_icon'],
                'total' => 0,
            );
            $out['counts'][$row['pc_id']]['title'] = $row['pc_title'];
            $out['counts'][$row['pc_id']]['types'][$row['tp_id']] = $row['cnt'];
        }
        foreach ($out['counts'] as $pcid => $data) {
            foreach ($out['types'] as $tid => $cnt) {
                if (!isset($data['types'][$tid])) {
                    $out['counts'][$pcid]['types'][$tid] = 0;
                }
                $out['types'][$tid]['total'] += $out['counts'][$pcid]['types'][$tid];
            }
        }
        return $out;
    }

    /**
     * Исправляет форматы данных
     */
    public function repairData() {
        $this->_db->sql = "UPDATE $this->_table_name SET
                                cp_phone = REPLACE(cp_phone, ';', ',')
                            WHERE cp_phone LIKE '%;%'";
        $this->_db->exec();
        $this->_db->sql = "UPDATE $this->_table_name SET
                                cp_phone = REPLACE(cp_phone, ' ,', ',')
                            WHERE cp_phone LIKE '% ,%'";
        $this->_db->exec();
        $this->_db->sql = "UPDATE $this->_table_name SET
                                cp_title = TRIM(TRAILING '.' FROM cp_title)
                            WHERE cp_title LIKE '%.'";
        $this->_db->exec();
        $this->_db->sql = "UPDATE $this->_table_name SET
                                cp_addr = TRIM(TRAILING '.' FROM cp_addr)
                            WHERE cp_addr LIKE '%.'";
        $this->_db->exec();
        $this->_db->sql = "UPDATE $this->_table_name SET
                                cp_text = REPLACE(cp_text, '&nbsp;', ' ')
                            WHERE cp_text LIKE '%&nbsp;%'";
        $this->_db->exec();
        $this->_db->sql = "UPDATE $this->_table_name SET
                                cp_title = REPLACE(cp_title, '&nbsp;', ' ')
                            WHERE cp_title LIKE '%&nbsp;%'";
        $this->_db->exec();
    }

}
