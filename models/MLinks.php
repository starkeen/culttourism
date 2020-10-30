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
            'content_title',
            'redirect_url',
            'is_ok',
        ];

        parent::__construct($db);

        $this->addRelatedTable('pagepoints');
        $this->addRelatedTable('pagecity');
        $this->addRelatedTable('region_url');
        $this->addRelatedTable('ref_pointtypes');
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

    /**
     * @param int $count
     *
     * @return array[]
     */
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

    /**
     * @param int $id
     * @param int $statusCode
     * @param int $statusCount
     * @param int|null $contentSize
     * @param string|null $contentTitle
     * @param string|null $redirectUrl
     */
    public function updateStatus(int $id, int $statusCode, int $statusCount, ?int $contentSize, ?string $contentTitle, ?string $redirectUrl = null): void
    {
        $isOk = $statusCode === 200 && $contentSize > 2000;

        if ($contentTitle !== null) {
            foreach (['домен', 'domain', 'прода', 'откл'] as $keyword) {
                if (mb_strpos($contentTitle, $keyword) !== false) {
                    $isOk = false;
                }
            }
        }

        $this->updateByPk(
            $id,
            [
                'status' => $statusCode,
                'status_count' => $statusCount,
                'status_date' => $this->now(),
                'content_size' => $contentSize,
                'content_title' => $contentTitle,
                'redirect_url' => $redirectUrl,
                'is_ok' => $isOk,
            ]
        );
    }

    /**
     * @param int $objectId
     */
    public function deleteByPoint(int $objectId): void
    {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE id_object = :object_id";
        $this->_db->execute(
            [
                ':object_id' => $objectId,
            ]
        );
    }

    /**
     * @param int $count
     * @param int|null $status
     * @param int|null $type
     *
     * @return array[]
     */
    public function getHandProcessingList(int $count, ?int $status, ?int $type): array
    {
        $params = [
            ':limit' => $count,
        ];
        $this->_db->sql = "SELECT u.*,
                             ROUND(u.content_size / 1024, 1) AS content_kb,
                             o.pt_name, o.pt_adress,
                             pt.tp_icon, pt.tp_short, pt.tp_name,
                             c.pc_title_unique,
                             CONCAT(url.url, '/') AS url_city,
                             CONCAT(url.url, '/', o.pt_slugline, '.html') AS url_point
                           FROM $this->_table_name AS u
                           LEFT JOIN {$this->_tables_related['pagepoints']} AS o ON o.pt_id = u.id_object
                           LEFT JOIN {$this->_tables_related['pagecity']} AS c ON c.pc_id = o.pt_citypage_id
                           LEFT JOIN {$this->_tables_related['region_url']} AS url ON url.uid = c.pc_url_id
                           LEFT JOIN {$this->_tables_related['ref_pointtypes']} pt ON pt.tp_id = o.pt_type_id
                           WHERE u.is_ok = 0
                             AND u.status_count > 2
                             AND o.pt_active = 1 \n";
        if ($status !== null) {
            $this->_db->sql .= "AND u.status = :status\n";
            $params[':status'] = $status;
        }
        if ($type !== null) {
            $this->_db->sql .= "AND o.pt_type_id = :type\n";
            $params[':type'] = $type;
        }
        $this->_db->sql .= "ORDER BY u.status_count DESC, c.pc_order DESC, c.pc_count_points DESC, u.status DESC, o.pt_rank DESC
                            LIMIT :limit";

        $this->_db->execute($params);

        return $this->_db->fetchAll();
    }

    public function getHandProcessingStatuses(): array
    {
        $this->_db->sql = "SELECT u.status, COUNT(1) as cnt
                           FROM $this->_table_name AS u
                           LEFT JOIN {$this->_tables_related['pagepoints']} AS o ON o.pt_id = u.id_object
                           WHERE u.is_ok = 0
                             AND u.status_count > 2
                             AND o.pt_active = 1
                           GROUP BY u.status";
        $this->_db->exec();

        return $this->_db->fetchAll();
    }

    public function getHandProcessingTypes(): array
    {
        $this->_db->sql = "SELECT pt.tp_id, pt.tp_icon, pt.tp_short, pt.tp_name,
                            COUNT(1) as cnt
                           FROM $this->_table_name AS u
                           LEFT JOIN {$this->_tables_related['pagepoints']} AS o ON o.pt_id = u.id_object
                           LEFT JOIN {$this->_tables_related['ref_pointtypes']} pt ON pt.tp_id = o.pt_type_id
                           WHERE u.is_ok = 0
                             AND u.status_count > 2
                             AND o.pt_active = 1
                           GROUP BY o.pt_type_id";
        $this->_db->exec();

        return $this->_db->fetchAll();
    }
}
