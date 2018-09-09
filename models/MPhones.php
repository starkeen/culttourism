<?php

declare(strict_types=1);

namespace models;

use app\db\MyDB;
use Model;
use MPagePoints;

class MPhones extends Model
{
    protected $_table_pk = 'id';
    protected $_table_order = 'id';
    protected $_table_active = 'id';

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('phones');
        $this->_table_fields = [
            'phone_raw',
            'id_point',
            'id_city',
        ];
        parent::__construct($db);
    }
}
