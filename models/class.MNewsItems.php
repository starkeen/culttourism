<?php

class MNewsItems extends Model {

    protected $_table_pk = 'ni_id';
    protected $_table_order = 'ni_id';
    protected $_table_active = 'ni_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('news_items');
        $this->_table_fields = array(
            'ni_id',
            'ni_ns_id',
            'ni_pubdate',
            'ni_title',
            'ni_url',
            'ni_text',
            'ni_active',
        );
        parent::__construct($db);
        $this->addRelatedTable('news_sourses');
    }

    public function cleanExpired() {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE ni_pubdate < SUBDATE(NOW(), INTERVAL 3 DAY)";
        $this->_db->exec();
    }

    /**
     * Добавление новости в кэш
     * @param array $data
     */
    public function add($data) {
        $this->_db->sql = "INSERT INTO $this->_table_name
                                (ni_ns_id, ni_pubdate, ni_title, ni_url, ni_text, ni_active)
                            VALUES
                                (:ns_id, :pubdate, :title1, :link, :text1, 1)
                            ON DUPLICATE KEY UPDATE ni_title = :title2, ni_text = :text2";
        $this->_db->execute(array(
            ':ns_id' => $data['source_id'],
            ':pubdate' => $data['pubdate'],
            ':title1' => $data['title'],
            ':title2' => $data['title'],
            ':link' => $data['link'],
            ':text1' => $data['description'],
            ':text2' => $data['description'],
        ));
    }

    public function getLastWithTS($limit) {
        $out = array(
            'entries' => array(),
            'max_ts' => 0,
        );
        $this->_db->sql = "SELECT *,
                                UNIX_TIMESTAMP(ni.ni_pubdate) AS last_update,
                                DATE_FORMAT(ni.ni_pubdate,'%d.%m.%Y') as datex
                            FROM $this->_table_name ni
                                LEFT JOIN {$this->_tables_related['news_sourses']} ns ON ns.ns_id = ni.ni_ns_id
                            WHERE ni.ni_active = 1
                            GROUP BY ni_title
                            ORDER BY ni_pubdate DESC
                            LIMIT :limit";
        $this->_db->execute(array(
            ':limit' => (int) $limit,
        ));
        while ($row = $this->_db->fetch()) {
            $row['ni_text'] = strip_tags(html_entity_decode($row['ni_text'], ENT_QUOTES));
            $row['ni_text'] = trim(mb_substr($row['ni_text'], 0, mb_strrpos(mb_substr($row['ni_text'], 0, 350, 'utf-8'), '.', 'utf-8'), 'utf-8'), '\,');
            $sourse_url = parse_url($row['ns_web']);
            $row['ns_host'] = $sourse_url['host'];
            $out['entries'][] = $row;
            if ($row['last_update'] > $out['max_ts']) {
                $out['max_ts'] = $row['last_update'];
            }
        }
        return $out;
    }

}
