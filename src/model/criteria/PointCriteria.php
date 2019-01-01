<?php

declare(strict_types=1);

namespace app\model\criteria;

class PointCriteria
{
    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var string[]
     */
    private $orderBy = [];

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * @return string[]
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @return string
     */
    public function getOrderString(): string
    {
        $result = [];
        foreach ($this->orderBy as $field => $direction) {
            $result[] = $field . ' ' . $direction;
        }
        return implode(', ', $result);
    }

    /**
     * @param string[] $orderBy
     */
    public function setOrderBy(array $orderBy): void
    {
        $this->orderBy = $orderBy;
    }

    /**
     * @param string $field
     * @param string $direction
     */
    public function addOrder(string $field, string $direction = 'ASC'): void
    {
        $this->orderBy[$field] = $direction;
    }
}
