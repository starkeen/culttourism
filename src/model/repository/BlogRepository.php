<?php

declare(strict_types=1);

namespace app\model\repository;

use app\db\MyDB;
use app\model\entity\BlogEntry;
use app\model\entity\User;

class BlogRepository
{
    /**
     * @var MyDB
     */
    private $db;

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $count
     * @param bool $withHidden
     * @return BlogEntry[]
     */
    public function getLastEntries(int $count, bool $withHidden): array
    {
        $result = [];

        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');
        $this->db->sql = "SELECT bg.*, us.us_name,
                    IF(bg.br_date < now(),1,0) as br_showed
                    FROM $dbb bg
                    LEFT JOIN $dbu us ON bg.br_us_id = us.us_id" . PHP_EOL;
        if (!$withHidden) {
            $this->db->sql .= 'HAVING br_showed = 1' . PHP_EOL;
        }
        $this->db->sql .= 'ORDER BY bg.br_date DESC
                           LIMIT :limit';

        $this->db->execute(
            [
                ':limit' => $count,
            ]
        );

        while ($row = $this->db->fetch()) {
            $entry = new BlogEntry($row);
            $entry->setOwner(new User([
                'us_id' => $row['br_us_id'],
                'us_name' => $row['us_name'],
            ]));
            $result[] = $entry;
        }

        return $result;
    }

    /**
     * @param string $key
     * @param int $month
     * @param int $year
     * @return BlogEntry|null
     */
    public function getItem(string $key, int $month, int $year): ?BlogEntry
    {
        $result = null;
        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');

        $this->db->sql = "SELECT *
                          FROM $dbb bg
                              LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                          WHERE DATE_FORMAT(br_date, '%Y-%c') = :date_key
                              AND (bg.br_url = :url_key OR DATE_FORMAT(br_date, '%e') = :day_key)
                          AND br_active = 1
                          LIMIT 1";
        $res = $this->db->execute(
            [
                ':date_key' => "$year-$month",
                ':url_key' => $key,
                ':day_key' => $key,
            ]
        );
        if ($res) {
            $row = $this->db->fetch();
            if ($row !== null) {
                $result = new BlogEntry($row);
                $result->setOwner(new User([
                    'us_id' => $row['br_us_id'],
                    'us_name' => $row['us_name'],
                ]));
            }
        }

        return $result;
    }

    /**
     * @param int $id
     */
    public function deleteItem(int $id): void
    {
        $dbb = $this->db->getTableName('blogentries');

        $this->db->sql = "DELETE
                          FROM $dbb bg
                          WHERE br_id = :id";
        $this->db->execute(
            [
                ':id' => $id,
            ]
        );
    }
}
