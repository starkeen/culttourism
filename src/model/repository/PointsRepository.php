<?php

declare(strict_types=1);

namespace app\model\repository;

use app\model\entity\Point;

class PointsRepository extends Repository
{
    /**
     * @param  int $id
     * @return Point|null
     */
    public function getItemByPk(int $id): ?Point
    {
        $result = null;
        $tablePoints = $this->getDb()->getTableName(self::getTableName());

        $this->getDb()->sql = "SELECT *
                          FROM $tablePoints pt
                          WHERE pt_id = :id";
        $res = $this->getDb()->execute(
            [
                ':id' => $id,
            ]
        );
        if ($res) {
            $row = $this->getDb()->fetch();
            if ($row !== null) {
                $result = new Point($row);
            }
        }

        return $result;
    }

    protected static function getTableName(): string
    {
        return 'pagepoints';
    }

    protected static function getPkName(): string
    {
        return 'pt_id';
    }
}
