<?php

declare(strict_types=1);

namespace app\model\repository;

use app\db\MyDB;
use app\model\entity\CandidateBlockedDomain;

class CandidateDomainBlacklistRepository
{
    private const TABLE_NAME = 'candidate_domains_blacklist';

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

        $table = $this->db->getTableName(self::TABLE_NAME);
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

    public function append(string $domain): void
    {
        $table = $this->db->getTableName(self::TABLE_NAME);
        $this->db->sql = "INSERT INTO $table
                          SET domain = :domain, weight = 1, created_at = NOW(), last_at = NOW(), active = 0
                          ON DUPLICATE KEY UPDATE weight = weight+1, last_at = NOW()";
        $this->db->execute([
            ':domain' => $domain,
        ]);
    }
}
