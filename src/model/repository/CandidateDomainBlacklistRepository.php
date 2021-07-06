<?php

declare(strict_types=1);

namespace app\model\repository;

use app\model\entity\CandidateBlockedDomain;

class CandidateDomainBlacklistRepository extends Repository
{
    /**
     * @return CandidateBlockedDomain[]
     */
    public function getActualList(): array
    {
        $result = [];

        $table = $this->db->getTableName(self::getTableName());
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
            $result[] = $item->domain;
        }

        return $result;
    }

    public function append(string $domain): void
    {
        $table = $this->db->getTableName(self::getTableName());
        $this->db->sql = "INSERT INTO $table
                          SET domain = :domain, weight = 1, created_at = NOW(), last_at = NOW(), active = 0
                          ON DUPLICATE KEY UPDATE weight = weight+1, last_at = NOW()";
        $this->db->execute([
            ':domain' => $domain,
        ]);
    }

    public function getEntityByDomain(?string $domain): ?CandidateBlockedDomain
    {
        $result = null;

        if ($domain !== null && $domain !== '') {
            $table = $this->db->getTableName(self::getTableName());
            $this->db->sql = "SELECT *
                            FROM $table
                            WHERE domain = :domain
                            LIMIT 1";
            $this->db->execute([':domain' => $domain]);
            while ($row = $this->db->fetch()) {
                $result = new CandidateBlockedDomain($row);
            }
        }

        return $result;
    }

    protected static function getTableName(): string
    {
        return 'candidate_domains_blacklist';
    }

    protected static function getPkName(): string
    {
        return 'id';
    }
}
