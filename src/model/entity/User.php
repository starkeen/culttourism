<?php

declare(strict_types=1);

namespace app\model\entity;

/**
 * @property int $us_id
 * @property string $us_name
 * @property string $us_login
 * @property string $us_passwrd
 * @property int $us_male
 * @property int $us_admin
 * @property string $us_email
 * @property int $us_active
 * @property int $us_level_id
 */
class User extends Entity
{
    /**
     * @param array $row
     */
    public function __construct(array $row)
    {
        $this->values = $row;
    }
}
