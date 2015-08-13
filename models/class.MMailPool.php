<?php

class MMailPool extends Model {

    protected $_table_pk = 'ml_id';
    protected $_table_order = 'ml_datecreate';
    protected $_table_active = 'ml_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('mail_pool');
        $this->_table_fields = array(
            'ml_adr_to',
            'ml_datecreate',
            'ml_theme',
            'ml_text',
            'ml_customheader',
            'ml_inwork',
            'ml_worked',
            'ml_sender_id',
            'ml_datesend',
        );
        parent::__construct($db);
    }

    public function getFiltered($filter = array()) {
        if (isset($filter['email'])) {
            $filter['email'] = $this->_db->getEscapedString($filter['email']);
            $filter['where'][] = "ml_adr_to = '{$filter['email']}'\n";
            unset($filter['email']);
        }
        if (isset($filter['header'])) {
            $filter['header'] = $this->_db->getEscapedString($filter['header']);
            $filter['where'][] = "ml_customheader = 'X-Mailru-Msgtype: {$filter['header']}'\n";
            unset($filter['header']);
        }

        $out = parent::getItemsByFilter($filter);
        foreach ($out['items'] as $i => $item) {
            $out['items'][$i]['ml_text_short'] = substr(strip_tags($item['ml_text']), 0, 150);
        }
        return $out;
    }

    public function getStatAll() {
        $this->_db->sql = "SELECT count(1) AS cnt FROM $this->_table_name";
        $this->_db->exec();
        $row = $this->_db->fetch();
        return $row['cnt'];
    }

    public function markWorked($id) {
        $this->_db->sql = "UPDATE $this->_table_name SET
                            ml_worked = 1, ml_inwork=0, ml_datesend = NOW()
                            WHERE ml_id = :mid";
        $this->_db->prepare();
        return $this->_db->execute(array(
                    ':mid' => $id,
        ));
    }

    public function markInwork($id) {
        $this->_db->sql = "UPDATE $this->_table_name SET
                            ml_inwork=1
                            WHERE ml_id = :mid";
        $this->_db->prepare();
        return $this->_db->execute(array(
                    ':mid' => $id,
        ));
    }

    public function getPortion($count) {
        $this->_db->sql = "SELECT ml_id FROM $this->_table_name
                    WHERE ml_worked = 0
                    AND ml_inwork = 0
                    LIMIT :limit";
        $this->_db->prepare();
        $this->_db->execute(array(
            ':limit' => $count,
        ));
        return $this->_db->fetchAll();
    }

}
