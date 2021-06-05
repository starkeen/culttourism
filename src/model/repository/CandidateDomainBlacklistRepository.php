<?php

declare(strict_types=1);

namespace app\model\repository;

use app\db\MyDB;
use app\model\entity\CandidateBlockedDomain;

class CandidateDomainBlacklistRepository
{
    /**
     * @var MyDB
     */
    private MyDB $db;

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->db = $db;
    }

    /**
     * @return CandidateBlockedDomain[]
     */
    public function getActualList(): array
    {
        $result = [];

        $table = $this->db->getTableName('candidate_domains_blacklist');
        $this->db->sql = "SELECT domain
                            FROM $table
                            WHERE active = 1";
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            $entry = new CandidateBlockedDomain($row);
            $result[] = $entry;
        }

        return $result;
    }

    /**
     * @return string[]
     */
    public function getActualDomainsList(): array
    {
        $list = $this->getActualList();

        $result = [];
        foreach ($list as $item) {
            $result[] = $item;
        }

        return $result;
    }
}
