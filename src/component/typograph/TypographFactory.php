<?php

declare(strict_types=1);

namespace app\component\typograph;

use JoliTypo\Fixer;
use JoliTypo\Fixer\Dash;

class TypographFactory
{
    public static function build(): Typograph
    {
        $fixer = new Fixer([Dash::class]);

        return new Typograph($fixer);
    }
}
