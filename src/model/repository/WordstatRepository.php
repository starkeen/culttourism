<?php

declare(strict_types=1);

namespace app\model\repository;

use app\db\MyDB;
use app\model\entity\Wordstat;

class WordstatRepository
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
     * @param  string $name
     * @return Wordstat|null
     */
    public function getDataByCityName(string $name): ?Wordstat
    {
        $dbws = $this->db->getTableName('wordstat');
        $this->db->sql = "SELECT *
                    FROM $dbws
                    WHERE ws_city_title = :pc_title
                    LIMIT 1";
        $this->db->execute(
            [
                ':pc_title' => $name,
            ]
        );
        $data = $this->db->fetch();

        return $data !== null ? new Wordstat($data) : null;
    }
}
