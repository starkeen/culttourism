<?php

declare(strict_types=1);

namespace app\constant;

use MyCLabs\Enum\Enum;

/**
 * @method static self APP_ID()
 * @method static self TYPE()
 * @method static self SITE_NAME()
 * @method static self URL()
 * @method static self TITLE()
 * @method static self DESCRIPTION()
 * @method static self IMAGE()
 * @method static self UPDATED_TIME()
 * @method static self LOCALE()
 */
class OgType extends Enum
{
    public const APP_ID = 'app_id';
    public const TYPE = 'type';
    public const SITE_NAME = 'site_name';
    public const URL = 'url';
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const IMAGE = 'image';
    public const UPDATED_TIME = 'updated_time';
    public const LOCALE = 'locale';
}
