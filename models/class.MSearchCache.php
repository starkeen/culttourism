<?php

use app\db\MyDB;

class MSearchCache extends Model
{
    protected $_table_pk = 'sc_id';
    protected $_table_order = 'sc_date';
    protected $_table_active = 'sc_id';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('search_cache');
        $this->_table_fields = [
            'sc_date',
            'sc_session',
            'sc_query',
            'sc_sr_id',
        ];
        parent::__construct($db);
    }

    public function add($data)
    {
        $data['sc_date'] = $this->now();
        $data['sc_sr_id'] = 0;

        return $this->insert($data);
    }
}
