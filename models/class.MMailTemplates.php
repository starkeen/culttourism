<?php

use app\db\MyDB;

class MMailTemplates extends Model
{
    protected $_table_pk = 'mt_id';
    protected $_table_order = 'mt_id';
    protected $_table_active = 'mt_id';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('mail_templates');
        $this->_table_fields = [
            'mt_content',
            'mt_description',
            'mt_theme',
            'mt_custom_header',
        ];
        parent::__construct($db);
    }
}
