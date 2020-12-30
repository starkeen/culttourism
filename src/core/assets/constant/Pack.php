<?php

declare(strict_types=1);

namespace app\core\assets\constant;

use MyCLabs\Enum\Enum;

/**
 * @method static self COMMON()
 * @method static self API()
 * @method static self MAP()
 * @method static self LIST()
 * @method static self CITY()
 * @method static self POINT()
 * @method static self EDITOR()
 */
class Pack extends Enum
{
    public const COMMON = 'common';
    public const API = 'api';
    public const MAP = 'map';
    public const LIST = 'list';
    public const CITY = 'city';
    public const POINT = 'point';
    public const EDITOR = 'editor';
}
