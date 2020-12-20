<?php

declare(strict_types=1);

namespace app\model\entity;

abstract class Entity
{
    /**
     * @var int[]|string[]|bool[]|null[]
     */
    protected $values = [];

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->values[$name]);
    }

    /**
     * @param string $name
     * @param int|null|string|bool $value
     */
    public function __set(string $name, $value): void
    {
        $this->values[$name] = $value;
    }

    /**
     * @param string $name
     * @return int|string|bool|null
     */
    public function __get(string $name)
    {
        return $this->values[$name] ?? null;
    }
}
