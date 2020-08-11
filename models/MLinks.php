<?php

declare(strict_types=1);

namespace models;

use app\db\MyDB;
use Model;

class MLinks extends Model
{
    protected $_table_pk = 'id';
    protected $_table_order = 'id';
    protected $_table_active = 'id';

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('links');
        $this->_table_fields = [
            'url',
            'id_object',
            'status',
            'status_date',
        ];

        parent::__construct($db);

        $this->_addRelatedTable('pagepoints');
    }

    /**
     */
    public function makeCache(): void
    {
        $this->_db->sql = "INSERT $this->_table_name (url)
                            (SELECT pt_website FROM {$this->_tables_related['pagecity']} AS o WHERE pt_website IS NOT NULL AND pt_website != '')
                           ON DUPLICATE KEY UPDATE id_object = o.id";
        $this->_db->exec();
    }
}
