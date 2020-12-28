<?php

use app\db\MyDB;

class MWeatherCodes extends Model
{
    protected $_table_pk = 'wc_id';
    protected $_table_order = 'wc_id';
    protected $_table_active = 'wc_id';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('weather_codes');
        $this->_table_fields = [
            'wc_id',
            'wc_main',
            'wc_description',
        ];
        parent::__construct($db);
    }
}
