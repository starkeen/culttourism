<?php

declare(strict_types=1);

namespace models;

use app\db\MyDB;
use app\exceptions\MyPDOException;
use Model;

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

    /**
     * @param int $pointId
     *
     * @throws MyPDOException
     */
    public function deleteByPoint(int $pointId): void
    {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE id_point = :pointId";

        $this->_db->execute(
            [
                ':pointId' => $pointId,
            ]
        );
    }
}
