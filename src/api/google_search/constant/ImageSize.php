<?php

declare(strict_types=1);

namespace app\api\google_search\constant;

use MyCLabs\Enum\Enum;

/**
 * @method static self ICON()
 * @method static self SMALL()
 * @method static self MEDIUM()
 * @method static self LARGE()
 * @method static self XLARGE()
 * @method static self XXLARGE()
 * @method static self HUGE()
 */
class ImageSize extends Enum
{
    public const ICON = 'icon'; // Only very small icon-sized images.
    public const SMALL = 'small'; // Only small images.
    public const MEDIUM = 'medium'; // Only medium images.
    public const LARGE = 'large'; // Only large images.
    public const XLARGE = 'xlarge'; // Only very large images.
    public const XXLARGE = 'xxlarge'; // Only extremely large images.
    public const HUGE = 'huge'; // Only the largest possible images.
}
