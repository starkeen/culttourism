<?php

declare(strict_types=1);

namespace app\model\repository;

class RedirectsRepository extends Repository
{
    protected static function getTableName(): string
    {
        return 'redirects';
    }

    protected static function getPkName(): string
    {
        return 'rd_id';
    }
}
