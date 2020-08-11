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
            'fetch_date',
            'last_date',
            'status',
            'status_date',
            'content_size',
            'is_ok',
        ];

        parent::__construct($db);

        $this->_addRelatedTable('pagepoints');
    }

    /**
     */
    public function makeCache(): void
    {
        $this->_db->sql = "INSERT $this->_table_name (id_object, url, fetch_date, last_date)
                            (SELECT pt_id, pt_website, NOW(), NOW() FROM {$this->_tables_related['pagepoints']} AS o WHERE pt_website IS NOT NULL AND pt_website != '')
                           ON DUPLICATE KEY UPDATE url = pt_website, last_date = NOW()";
        $this->_db->exec();
    }

    public function getCheckPortion(int $count = 10): array
    {
        $this->_db->sql = "SELECT *
                           FROM $this->_table_name
                           ORDER BY status_date ASC
                           LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => $count,
            ]
        );

        return $this->_db->fetchAll();
    }

    public function updateStatus(int $id, int $statusCode, ?int $contentSize): void
    {
        $isOk = in_array($statusCode, [200], true) && $contentSize > 5000;

        $this->updateByPk(
            $id,
            [
                'status' => $statusCode,
                'status_date' => $this->now(),
                'content_size' => $contentSize,
                'is_ok' => $isOk,
            ]
        );
    }
}
