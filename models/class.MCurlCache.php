<?php

class MCurlCache extends Model {

    protected $_table_pk = 'cc_id';
    protected $_table_order = 'cc_date';
    protected $_table_active = 'cc_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('curl_cache');
        $this->_table_fields = array(
            'cc_date',
            'cc_url',
            'cc_text',
            'cc_expire',
        );
        parent::__construct($db);
    }

    public function get($url) {
        $this->_db->sql = "SELECT * FROM $this->_table_name WHERE cc_url = :url";

        $this->_db->execute(array(
            ':url' => $url,
        ));
        $row = $this->_db->fetch();
        return !empty($row) ? $row['cc_text'] : null;
    }

    public function put($url, $text, $expire = 3600) {
        $this->_db->sql = "INSERT INTO $this->_table_name
                            SET
                                cc_date = NOW(),
                                cc_url = :url,
                                cc_text = :text1,
                                cc_expire = DATE_ADD(NOW(), INTERVAL :expire1 SECOND)
                            ON DUPLICATE KEY UPDATE
                                cc_date = NOW(),
                                cc_text = :text2,
                                cc_expire = DATE_ADD(NOW(), INTERVAL :expire2 SECOND)";

        $this->_db->execute(array(
            ':url' => $url,
            ':text1' => $text,
            ':text2' => $text,
            ':expire1' => $expire,
            ':expire2' => $expire,
        ));
    }

}
