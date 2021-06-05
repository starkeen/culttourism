<?php

declare(strict_types=1);

namespace app\model\entity;

/**
 * @property int|null $id
 * @property string $domain
 * @property int $weight
 * @property string $created_at
 * @property string $last_at
 * @property int $active
 */
class CandidateBlockedDomain extends Entity
{
    /**
     * @param array $row
     */
    public function __construct(array $row)
    {
        $this->values = $row;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
