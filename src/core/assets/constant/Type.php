<?php

declare(strict_types=1);

namespace app\core\assets\constant;

use MyCLabs\Enum\Enum;

/**
 * @method static self CSS()
 * @method static self JS()
 */
class Type extends Enum
{
    public const CSS = 'css';
    public const JS = 'js';

    private $contentTypes = [
        self::CSS => 'text/css',
        self::JS => 'text/javascript',
    ];

    public function getContentType(): string
    {
        return $this->contentTypes[$this->getValue()];
    }
}
