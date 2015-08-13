<?php

class MCron extends Model {

    protected $_table_pk = 'cr_id';
    protected $_table_order = 'cr_id';
    protected $_table_active = 'cr_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('cron');
        $this->_table_fields = array(
            'cr_id',
            'cr_title',
            'cr_script',
            'cr_datelast',
            'cr_datelast_attempt',
            'cr_datenext',
            'cr_period',
            'cr_lastresult',
            'cr_lastexectime',
            'cr_isrun',
            'cr_active',
        );
        parent::__construct($db);
    }
    
    public function killPhantoms() {
        $this->_db->sql = "UPDATE $this->_table_name
            SET cr_isrun = 0
            WHERE cr_isrun = 1
            AND cr_active = 1
            AND cr_datelast_attempt < SUBTIME(NOW(), '02:00:00')";
        $this->_db->exec();
    }
    
    public function getPortion() {
        $this->_db->sql = "SELECT *, DATE_FORMAT(cr_period, '%d %H:%i') as period FROM $this->_table_name
                WHERE cr_active = 1 AND cr_isrun = 0 AND cr_datenext <= NOW()";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }
    
    public function markWorkStart($id) {
        $this->_db->sql = "UPDATE $this->_table_name SET cr_isrun = 1, cr_datelast_attempt = NOW() WHERE cr_id = :crid";
        
        $this->_db->execute(array(
            ':crid' => $id,
        ));
    }
    
    public function markWorkFinish($id, $content, $exectime) {
        $this->_db->sql = "UPDATE $this->_table_name SET
                    cr_isrun = 0,
                    cr_lastexectime = :exectime,
                    cr_lastresult = :content,
                    cr_datenext = DATE_ADD(cr_datenext, INTERVAL DATE_FORMAT(cr_period, '%d %H:%i') DAY_MINUTE),
                    cr_datelast = NOW()
                    WHERE cr_id = :crid";
        
        $this->_db->execute(array(
            ':crid' => $id,
            ':content' => $content,
            ':exectime' => $exectime,
        ));
    }

}