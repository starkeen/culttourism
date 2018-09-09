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

        $this->_addRelatedTable('pagecity');
        $this->_addRelatedTable('city_data');
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

    /**
     * @throws MyPDOException
     */
    public function process(): void
    {
        $this->_db->sql = "UPDATE $this->_table_name AS t
                            LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = t.id_city
                            LEFT JOIN {$this->_tables_related['city_data']} city_data ON city_data.cd_pc_id = t.id_city AND cd.cd_cf_id = 2
                            SET t.code_country = NULL,
                                t.code_city = city_data.cd_value,
                                t.reversed = SUBSTRING(TRIM(REPLACE(REPLACE(REPLACE(REVERSE(t.phone_raw), '-', ''), '(', ''), ')', '')) FROM 0 FOR 5),
                                t.date_check = NOW()
                           WHERE t.date_check IS NULL
                           LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => 100,
            ]
        );
    }
}
