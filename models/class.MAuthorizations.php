<?php

use app\db\MyDB;

class MAuthorizations extends Model
{
    protected $_table_pk = 'au_id';
    protected $_table_order = 'au_id';
    protected $_table_active = 'au_id';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('authorizations');
        $this->_table_fields = [
            'au_id',
            'au_us_id',
            'au_key',
            'au_date_login',
            'au_date_last_act',
            'au_date_expire',
            'au_host',
            'au_service',
            'au_browser',
            'au_last_act',
            'au_ip',
            'au_session',
        ];
        parent::__construct($db);
    }

    public function cleanExpired(): void
    {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE au_date_expire < NOW()";
        $this->_db->exec();
    }

    public function cleanUnused(): void
    {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE au_service IN ('ajax', 'map')";
        $this->_db->exec();
    }
}
