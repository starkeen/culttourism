<?php

declare(strict_types=1);

namespace app\api\google_search\constant;

use MyCLabs\Enum\Enum;

/**
 * @method static self IMG_TYPE_UNDEFINED()
 * @method static self CLIPART()
 * @method static self FACE()
 * @method static self LINEART()
 * @method static self STOCK()
 * @method static self PHOTO()
 * @method static self ANIMATED()
 */
class ImageType extends Enum
{
    public const IMG_TYPE_UNDEFINED = 'IMG_TYPE_UNDEFINED'; // No image type specified.
    public const CLIPART = 'clipart'; // Clipart-style images only.
    public const FACE = 'face'; // Images of faces only.
    public const LINEART = 'lineart'; // Line art images only.
    public const STOCK = 'stock'; // Stock images only.
    public const PHOTO = 'photo'; // Photo images only.
    public const ANIMATED = 'animated'; // Animated images only.
}
