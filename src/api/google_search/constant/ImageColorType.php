<?php

declare(strict_types=1);

namespace app\api\google_search\constant;

use MyCLabs\Enum\Enum;

/**
 * @method static self IMG_COLOR_TYPE_UNDEFINED()
 * @method static self COLOR()
 * @method static self GRAY()
 * @method static self MONOCHROME()
 * @method static self TRANSPARENT()
 */
class ImageColorType extends Enum
{
    public const IMG_COLOR_TYPE_UNDEFINED = 'IMG_COLOR_TYPE_UNDEFINED';
    public const COLOR = 'color';
    public const GRAY = 'gray';
    public const MONOCHROME = 'mono';
    public const TRANSPARENT = 'trans';
}
