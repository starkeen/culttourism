<?php

declare(strict_types=1);

namespace app\model\criteria;

use app\core\exception\CoreException;

class PointCriteria
{
    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    /**
     * @var string[]
     */
    private $where = [];

    /**
     * @var int
     */
    private $limit = 1;

    /**
     * @var int
     */
    private $offset = 0;

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
    public function addOrder(string $field, string $direction = self::ORDER_ASC): void
    {
        if (!in_array($direction, [self::ORDER_ASC, self::ORDER_DESC], true)) {
            throw new CoreException('Неправильное направление сортировки');
        }
        $this->orderBy[$field] = $direction;
    }

    /**
     * @return string[]
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * @param string[] $where
     */
    public function setWhere(array $where): void
    {
        $this->where = $where;
    }

    /**
     * @param string $condition
     */
    public function addWhere(string $condition): void
    {
        $this->where[] = $condition;
    }

    /**
     * @return string
     */
    public function getWhereString(): string
    {
        return implode(' AND ', $this->where);
    }
}
