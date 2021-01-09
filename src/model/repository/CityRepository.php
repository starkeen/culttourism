<?php

declare(strict_types=1);

namespace app\model\repository;

class CityRepository extends Repository
{
    protected static function getTableName(): string
    {
        return 'pagecity';
    }

    protected static function getPkName(): string
    {
        return 'pc_id';
    }
}
