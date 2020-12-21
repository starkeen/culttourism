<?php

declare(strict_types=1);

namespace app\model\repository;

use app\db\MyDB;
use app\model\entity\BlogEntry;
use app\model\entity\User;
use app\utils\Dates;
use MBlogEntries;

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
     * @return BlogEntry|null
     */
    public function getItemByPk(int $id): ?BlogEntry
    {
        $result = null;
        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');

        $this->db->sql = "SELECT *
                          FROM $dbb bg
                              LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                          WHERE br_id = :id";
        $res = $this->db->execute(
            [
                ':id' => $id,
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

    /**
     * @param BlogEntry $entry
     * @return int|null
     */
    public function save(BlogEntry $entry): ?int
    {
        $bg = new MBlogEntries($this->db);
        $result = $entry->br_id;
        $row = [
            'br_title' => $entry->br_title,
            'br_text' => $entry->br_text,
            'br_date' => $entry->br_date,
            'br_active' => $entry->br_active,
            'br_url' => $entry->br_url,
            'br_us_id' => $entry->getOwner()->us_id,
        ];
        if ($result === null) {
            $result = $bg->insert($row);
        } else {
            $bg->updateByPk($result, $row);
        }

        return $result;
    }

    /**
     * @return int[]
     */
    public function getYears(): array
    {
        $dbb = $this->db->getTableName('blogentries');
        $this->db->sql = "SELECT DISTINCT DATE_FORMAT(bg.br_date,'%Y') as bg_year FROM $dbb AS bg ORDER BY bg_year";
        $this->db->exec();
        $years = [];
        while ($row = $this->db->fetch()) {
            $years[] = (int) $row['bg_year'];
        }

        return $years;
    }

    /**
     * @param int $year
     * @param int|null $month
     * @return array
     */
    public function getCalendarItems(int $year, int $month = null): array
    {
        $result = [];
        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');
        $binds = [
            ':year' => $year,
        ];
        $this->db->sql = "SELECT bg.*, us.us_name
                    FROM $dbb as bg
                        LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                    WHERE br_active = 1
                        AND DATE_FORMAT(br_date, '%Y') = :year\n";
        if ($month !== null) {
            $this->db->sql .= 'AND DATE_FORMAT(br_date, "%c") = :month' . PHP_EOL;
            $binds[':month'] = $month;
        }
        $this->db->sql .= 'AND br_date < NOW()
                           ORDER BY bg.br_date DESC';
        $this->db->execute($binds);

        while ($row = $this->db->fetch()) {
            $entry = new BlogEntry($row);
            $entry->setOwner(new User([
                'us_id' => $row['br_us_id'],
                'us_name' => $row['us_name'],
            ]));

            $month = $entry->getMonthNumber();
            $result[$month][] = $entry;
        }

        return $result;
    }
}
