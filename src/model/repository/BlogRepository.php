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
}
