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

}
