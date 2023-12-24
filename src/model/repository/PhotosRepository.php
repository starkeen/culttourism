<?php

declare(strict_types=1);

namespace app\model\repository;

use app\model\entity\Photo;

class PhotosRepository extends Repository
{
    protected static function getTableName(): string
    {
        return 'photos';
    }

    protected static function getPkName(): string
    {
        return 'ph_id';
    }

    /**
     * @param  int $id
     * @return Photo|null
     */
    public function getItemByPk(int $id): ?Photo
    {
        $result = null;
        $table = $this->getDb()->getTableName(self::getTableName());

        $this->getDb()->sql = "SELECT *
                          FROM $table ph
                          WHERE ph_id = :id";
        $res = $this->getDb()->execute(
            [
                ':id' => $id,
            ]
        );
        if ($res) {
            $row = $this->getDb()->fetch();
            if ($row !== null) {
                $result = new Photo($row);
            }
        }

        return $result;
    }
}
