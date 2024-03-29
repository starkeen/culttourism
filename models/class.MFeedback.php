<?php

use app\db\MyDB;

class MFeedback extends Model
{
    protected $_table_pk = 'fb_id';
    protected $_table_order = 'fb_date';
    protected $_table_active = 'fb_active';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('feedback');
        $this->_table_fields = [
            'fb_date',
            'fb_name',
            'fb_text',
            'fb_ip',
            'fb_browser',
            'fb_referer',
            'fb_sendermail',
            'fb_active',
        ];
        parent::__construct($db);
    }

    public function add($data)
    {
        $data['fb_date'] = $this->now();

        return $this->insert($data);
    }
}
