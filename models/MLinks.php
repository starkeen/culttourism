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
            'status_count',
            'status_date',
            'content_size',
            'is_ok',
        ];

        parent::__construct($db);

        $this->_addRelatedTable('pagepoints');
        $this->_addRelatedTable('pagecity');
        $this->_addRelatedTable('region_url');
    }

    /**
     */
    public function makeCache(): void
    {
        $this->_db->sql = "INSERT $this->_table_name (id_object, url, fetch_date, last_date)
                            (SELECT pt_id, pt_website, NOW(), NOW() FROM {$this->_tables_related['pagepoints']} AS o WHERE pt_website IS NOT NULL AND pt_website != '' AND pt_active = 1)
                           ON DUPLICATE KEY UPDATE url = pt_website, last_date = NOW()";
        $this->_db->exec();
    }

    public function getCheckPortion(int $count = 10): array
    {
        $this->_db->sql = "SELECT u.*, o.pt_name, c.pc_title_unique
                           FROM $this->_table_name AS u
                           LEFT JOIN {$this->_tables_related['pagepoints']} AS o ON o.pt_id = u.id_object
                           LEFT JOIN {$this->_tables_related['pagecity']} AS c ON c.pc_id = o.pt_citypage_id
                           WHERE o.pt_active = 1
                           ORDER BY status_date ASC
                           LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => $count,
            ]
        );

        return $this->_db->fetchAll();
    }

    public function updateStatus(int $id, int $statusCode, int $statusCount, ?int $contentSize): void
    {
        $isOk = in_array($statusCode, [200], true) && $contentSize > 5000;

        $this->updateByPk(
            $id,
            [
                'status' => $statusCode,
                'status_count' => $statusCount,
                'status_date' => $this->now(),
                'content_size' => $contentSize,
                'is_ok' => $isOk,
            ]
        );
    }

    public function deleteByPoint(int $objectId): void
    {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE id_object = :object_id";
        $this->_db->execute(
            [
                ':object_id' => $objectId,
            ]
        );
    }

    public function getList(int $count): array
    {
        $this->_db->sql = "SELECT u.*,
                             ROUND(u.content_size / 1024, 1) AS content_kb,
                             o.pt_name,
                             c.pc_title_unique,
                             CONCAT(url.url, '/') AS url_city,
                             CONCAT(url.url, '/', o.pt_slugline, '.html') AS url_point
                           FROM $this->_table_name AS u
                           LEFT JOIN {$this->_tables_related['pagepoints']} AS o ON o.pt_id = u.id_object
                           LEFT JOIN {$this->_tables_related['pagecity']} AS c ON c.pc_id = o.pt_citypage_id
                           LEFT JOIN {$this->_tables_related['region_url']} AS url ON url.uid = c.pc_url_id
                           WHERE u.is_ok = 0
                             AND u.status_count > 0
                             AND o.pt_active = 1
                           ORDER BY u.status_count DESC, c.pc_order DESC, c.pc_count_points DESC, u.status DESC
                           LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => $count,
            ]
        );

        return $this->_db->fetchAll();
    }
}

//SELECT  p.pt_name, c.pc_title_unique, l.url, l.status
//FROM `cult_links` l
//LEFT JOIN cult_pagepoints p ON p.pt_id = l.id_object
//LEFT JOIN cult_pagecity c ON c.pc_id = p.pt_citypage_id
//WHERE `is_ok` = 0 AND status > 301
//ORDER BY status DESC, c.pc_title_unique ASC