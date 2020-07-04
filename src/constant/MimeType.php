<?php

declare(strict_types=1);

namespace app\constant;

use MyCLabs\Enum\Enum;

/**
 * @method static self UNDEFINED()
 * @method static self JPEG()
 * @method static self PNG()
 * @method static self GIF()
 * @method static self WEBP()
 */
class MimeType extends Enum
{
    public const UNDEFINED = 'image/';
    public const JPEG = 'image/jpeg';
    public const PNG = 'image/png';
    public const GIF = 'image/gif';
    public const WEBP = 'image/webp';

    private $extensions = [
        self::UNDEFINED => '',
        self::JPEG => 'jpg',
        self::PNG => 'png',
        self::GIF => 'gif',
        self::WEBP => 'webp',
    ];

    public function getDefaultExtension(): string
    {
        return $this->extensions[$this->value];
    }
}
