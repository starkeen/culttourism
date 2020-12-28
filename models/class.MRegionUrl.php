<?php

use app\db\MyDB;

class MRegionUrl extends Model
{
    protected $_table_pk = 'uid';
    protected $_table_order = 'uid';
    protected $_table_active = 'uid';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('region_url');
        $this->_table_fields = [
            'url',
            'citypage',
        ];
        parent::__construct($db);
    }
}
