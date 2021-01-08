<?php

declare(strict_types=1);

namespace app\model\repository;

use app\db\MyDB;
use app\model\entity\Entity;

abstract class Repository
{
    private MyDB $db;

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->db = $db;
    }

    abstract protected static function getTableName(): string;
    abstract protected static function getPkName(): string;

    protected function getDb(): MyDB
    {
        return $this->db;
    }

    public function save(Entity $entity): int
    {
        $id = $entity->getId();
        if ($id === null) {
            $id = $this->insert($entity);
        } else {
            $this->update($entity);
        }

        return $id;
    }

    private function insert(Entity $entity): int
    {
        [$sqlSet, $binds] = $this->getSQLSetWithBinds($entity);

        $sql = 'INSERT INTO ' . $this->getDb()->getTableName(static::getTableName()) . ' SET ' . PHP_EOL
            . $sqlSet;

        $this->getDb()->setSQL($sql);
        $this->getDb()->execute($binds);

        return (int) $this->getDb()->getLastInserted();
    }

    private function update(Entity $entity): void
    {
        $pkName = static::getPkName();
        [$sqlSet, $binds] = $this->getSQLSetWithBinds($entity);

        $sql = 'UPDATE ' . $this->getDb()->getTableName(static::getTableName()) . ' SET ' . PHP_EOL
            . $sqlSet
            . 'WHERE ' . $pkName . ' = :' . $pkName;
        $binds[':' . $pkName] = $entity->getId();

        $this->getDb()->setSQL($sql);
        $this->getDb()->execute($binds);
    }

    /**
     * @param Entity $entity
     * @return array - два элемента - тело SQL-запроса и набор данных для него
     */
    private function getSQLSetWithBinds(Entity $entity): array
    {
        $binds = [];
        $sqlFields = [];

        $fields = $entity->getModifiedFields();
        foreach ($fields as $field) {
            $sqlFields[] = $field . ' = :' . $field;
            $binds[':' . $field] = $entity->$field;
        }

        return [
            implode(',' . PHP_EOL, $sqlFields) . PHP_EOL,
            $binds,
        ];
    }
}
