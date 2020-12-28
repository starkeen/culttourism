<?php

use app\db\MyDB;

class MNewsSources extends Model
{
    protected $_table_pk = 'ns_id';
    protected $_table_order = 'ns_id';
    protected $_table_active = 'ns_active';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('news_sourses');
        $this->_table_fields = [
            'ns_id',
            'ns_title',
            'ns_web',
            'ns_url',
            'ns_last_read',
            'ns_active',
        ];
        parent::__construct($db);
    }

    public function getPortion(): array
    {
        $this->_db->sql = "SELECT * FROM $this->_table_name WHERE ns_active = 1 ORDER BY ns_last_read LIMIT 1";
        $this->_db->exec();

        return $this->_db->fetchAll();
    }
}
