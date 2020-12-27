<?php

use app\db\MyDB;

class MCandidatePoints extends Model
{
    public const STATUS_NEW = 3;
    public const STATUS_SPAM = 7;

    public const SOURCE_FORM = 4;

    protected $_table_pk = 'cp_id';
    protected $_table_order = 'cp_date';
    protected $_table_active = 'cp_active';

    protected $typesMarkers;

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('candidate_points');
        $this->_table_fields = [
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
            'cp_hash',
            'cp_source_id',
            'cp_point_id',
            'cp_state',
            'cp_active',
        ];
        parent::__construct($db);

        $this->addRelatedTable('pagecity');
        $this->addRelatedTable('uniref_values');
        $this->addRelatedTable('ref_pointtypes');
        $this->addRelatedTable('region_url');
        $this->addRelatedTable('data_check');

        $rpt = new MRefPointtypes($this->_db);
        $this->typesMarkers = $rpt->getMarkers();
    }

    /**
     * @param array $data
     *
     * @return int|null
     */
    public function add(array $data): ?int
    {
        $data['cp_date'] = $this->now();
        $data['cp_state'] = $data['cp_state'] ?? self::STATUS_NEW;
        $data['cp_active'] = $data['cp_active'] ?? 1;
        if (empty($data['cp_title'])) {
            $data['cp_title'] = '[без названия]';
        }
        $data['cp_title'] = strip_tags($data['cp_title']);
        if (isset($data['cp_type_id']) && (int) $data['cp_type_id'] === 0) {
            foreach ($this->typesMarkers as $type => $markers) {
                foreach ((array) $markers as $marker) {
                    if (mb_stripos($data['cp_title'], $marker, 0, 'utf-8') !== false) {
                        $data['cp_type_id'] = $type;
                    }
                }
            }
        }

        if (empty($data['cp_city'])) {
            $data['cp_city'] = null;
        }
        if (empty($data['cp_addr'])) {
            $data['cp_addr'] = null;
        }
        if (empty($data['cp_phone'])) {
            $data['cp_phone'] = null;
        }
        if (empty($data['cp_web'])) {
            $data['cp_web'] = null;
        }
        if (empty($data['cp_worktime'])) {
            $data['cp_worktime'] = null;
        }

        if (!empty($data['cp_web']) && strpos($data['cp_web'], 'http') === false) {
            $data['cp_web'] = 'http://' . $data['cp_web'];
        }
        if (isset($data['cp_latitude'], $data['cp_longitude'])) {
            $data['cp_latitude'] = (float) $data['cp_latitude'];
            $data['cp_longitude'] = (float) $data['cp_longitude'];
        }

        $result = $this->insert($data);

        if ((int) $result > 0) {
            $hash = $this->getHash((int) $result);
            $this->updateByPk($result, ['cp_hash' => $hash]);
            if ($this->isSpam((int) $result, $hash)) {
                $this->updateByPk(
                    $result,
                    [
                        'cp_active' => 0,
                        'cp_state' => self::STATUS_SPAM,
                    ]
                );
            }
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * @param int $id
     * @return string (60 chars)
     */
    public function getHash(int $id): string
    {
        $row = $this->getItemByPk($id);
        $hashData = [
            $row['cp_title'],
            $row['cp_text'],
            $row['cp_web'],
        ];
        $hashString = implode('~|~', $hashData);

        return password_hash($hashString, PASSWORD_BCRYPT);
    }

    public function getByFilter(array $filter): array
    {
        $this->_db->sql = "SELECT t.*,
                                pc.pc_title AS page_title, CONCAT(u.url, '/') AS page_url,
                                uv_stat.uv_title AS state_title,
                                pt.tp_icon AS type_icon, pt.tp_short AS type_title,
                                CHAR_LENGTH(cp_text) AS text_len,
                                dc.dc_id
                            FROM $this->_table_name AS t
                                LEFT JOIN {$this->_tables_related['pagecity']} AS pc
                                    ON pc.pc_id = t.cp_citypage_id
                                    LEFT JOIN {$this->_tables_related['region_url']} AS u
                                        ON u.uid = pc.pc_url_id
                                LEFT JOIN {$this->_tables_related['uniref_values']} AS uv_stat
                                    ON uv_stat.uv_id = t.cp_state
                                LEFT JOIN {$this->_tables_related['ref_pointtypes']} AS pt
                                    ON pt.tp_id = t.cp_type_id
                                LEFT JOIN {$this->_tables_related['data_check']} AS dc
                                    ON dc.dc_item_id = t.cp_id AND dc_type = 'candidate_points' AND dc_field = 'cp_text'
                            WHERE 1\n";
        if (isset($filter['active']) && (int) $filter['active'] === 1) {
            $this->_db->sql .= "AND $this->_table_active = 1\n";
        }
        if (isset($filter['type']) && (int) $filter['type'] > 0) {
            $this->_db->sql .= "AND t.cp_type_id = '" . (int) $filter['type'] . "'\n";
        }
        if (isset($filter['type']) && (int) $filter['type'] === -1) {
            $this->_db->sql .= "AND (t.cp_type_id = 0 OR t.cp_type_id IS NULL)\n";
        }
        if (isset($filter['pcid']) && (int) $filter['pcid'] > 0) {
            $this->_db->sql .= "AND t.cp_citypage_id = '" . (int) $filter['pcid'] . "'\n";
        }
        if (isset($filter['gps']) && (int) $filter['gps'] === 1) {
            $this->_db->sql .= "AND t.cp_latitude > 0 AND t.cp_longitude > 0\n";
        }
        if (isset($filter['gps']) && (int) $filter['gps'] === -1) {
            $this->_db->sql .= "AND (t.cp_latitude = 0 OR t.cp_longitude = 0 OR t.cp_latitude IS NULL OR t.cp_longitude IS NULL)\n";
        }
        if (isset($filter['state']) && (int) $filter['state'] !== 0) {
            $this->_db->sql .= "AND t.cp_state = '" . (int) $filter['state'] . "'\n";
        }
        if (isset($filter['noHash']) && (int) $filter['noHash'] === 1) {
            $this->_db->sql .= "AND t.cp_hash IS NULL\n";
        }
        $this->_db->sql .= "ORDER BY $this->_table_order ASC\n";
        $this->_db->exec();

        return $this->_db->fetchAll();
    }

    /**
     * Статистика по набору активных заявок
     */
    public function getMatrix(): array
    {
        $this->_db->sql = "SELECT COUNT(1) AS cnt,
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
        $out = [
            'types' => [],
            'counts' => [],
        ];
        while ($row = $this->_db->fetch()) {
            $out['types'][$row['tp_id']] = [
                'title' => $row['tp_name'],
                'icon' => $row['tp_icon'],
                'total' => 0,
            ];
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
    public function repairData(): void
    {
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

        $this->_db->sql = "UPDATE $this->_table_name SET
                                cp_title = REPLACE(cp_title, '&laquo;', '«')
                            WHERE cp_title LIKE '%&laquo;%'";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name SET
                                cp_title = REPLACE(cp_title, '&raquo;', '»')
                            WHERE cp_title LIKE '%&raquo;%'";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name SET
                                cp_title = CONCAT(UCASE(LEFT(cp_title, 1)), SUBSTRING(cp_title, 2))";
        $this->_db->exec();
    }

    private function isSpam(int $id, string $hash): bool
    {
        $result = false;

        $this->_db->sql = "SELECT *
                            FROM $this->_table_name
                            WHERE cp_hash = :hash
                                AND cp_id != :id
                                AND cp_date > DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $this->_db->execute(
            [
                ':id' => $id,
                ':hash' => $hash,
            ]
        );
        $rows = $this->_db->fetchAll();
        $spamCount = 0;
        $totalCount = count($rows);
        foreach ($rows as $row) {
            if ((int) $row['cp_state'] === self::STATUS_SPAM) {
                $spamCount++;
            }
        }
        if ($totalCount > 0 && $spamCount === $totalCount) {
            $result = true;
        }

        return $result;
    }
}
