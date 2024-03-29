<?php

use app\db\MyDB;

class MLogActions extends Model
{
    protected $_table_pk = 'la_id';
    protected $_table_order = 'la_id';
    protected $_table_active = 'la_id';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('log_actions');
        $this->_table_fields = [
            'la_id',
            'la_date',
            'la_module',
            'la_action',
            'la_text',
        ];
        parent::__construct($db);
    }

    public function cleanExpired(): void
    {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE la_date < SUBDATE(NOW(), INTERVAL 60 DAY)";
        $this->_db->exec();
    }
}
