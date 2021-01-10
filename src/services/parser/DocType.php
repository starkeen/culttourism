<?php

declare(strict_types=1);

namespace app\services\parser;

use MyCLabs\Enum\Enum;

class DocType extends Enum
{
    public const XHTML1 = 'XHTML 1.0 Transitional';
    public const HTML41 = 'HTML 4.01 Transitional';
}
