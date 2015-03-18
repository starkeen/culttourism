<?php

class MBlogEntries extends Model {

    protected $_table_pk = 'br_id';
    protected $_table_order = 'br_date';
    protected $_table_active = 'br_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('blogentries');
        $this->_table_fields = array(
            'br_date',
            'br_title',
            'br_url',
            'br_text',
            'br_us_id',
            'br_active',
        );
        parent::__construct($db);
        $this->_addRelatedTable('users');
    }

    public function getLastActive($qnt = 10) {
        $this->_db->sql = "SELECT bg.br_id, bg.br_title, bg.br_text, 'Роберт' AS us_name,
                                DATE_FORMAT(bg.br_date,'%a, %d %b %Y %H:%i:%s GMT') as bg_pubdate,
                                DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                                IF(bg.br_url != '',
                                    CONCAT('" . _SITE_URL . "blog/', DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'),
                                    CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')
                                ) as br_link
                            FROM $this->_table_name
                            WHERE br_active = '1'
                                AND br_date < now()
                            ORDER BY bg.br_date DESC
                            LIMIT $qnt";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

}
