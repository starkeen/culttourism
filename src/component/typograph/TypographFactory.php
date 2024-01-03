<?php

declare(strict_types=1);

namespace app\component\typograph;

use JoliTypo\Fixer;

class TypographFactory
{
    public static function build(): Typograph
    {
        $fixer = new Fixer([]);

        return new Typograph($fixer);
    }
}
