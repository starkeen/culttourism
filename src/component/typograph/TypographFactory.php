<?php

declare(strict_types=1);

namespace app\component\typograph;

use EMT\EMTypograph;

class TypographFactory
{
    public static function build(): Typograph
    {
        $typograph = new EMTypograph();
        return new Typograph($typograph);
    }
}
