<?php

declare(strict_types=1);

namespace app\model\entity;

abstract class Entity
{
    /**
     * @var int[]|string[]|bool[]|null[]
     */
    protected array $values = [];

    protected array $modifiedFields = [];

    abstract public function getId(): ?int;

    public function beforeSave(): void
    {
        // имплементация возможна в конечных реализациях
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->values[$name]);
    }

    /**
     * @param string               $name
     * @param int|null|string|bool $value
     */
    public function __set(string $name, $value): void
    {
        $this->values[$name] = $value;
        $this->modifiedFields[$name] = true;
    }

    /**
     * @param  string $name
     * @return int|string|bool|null
     */
    public function __get(string $name)
    {
        return $this->values[$name] ?? null;
    }

    /**
     * @return string[]
     */
    public function getModifiedFields(): array
    {
        return array_keys(
            array_filter(
                $this->modifiedFields,
                static function (bool $fieldState) {
                    return $fieldState === true;
                }
            )
        );
    }

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
