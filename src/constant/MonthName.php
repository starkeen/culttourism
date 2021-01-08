<?php

declare(strict_types=1);

namespace app\constant;

use app\core\exception\CoreException;

class MonthName
{
    public const NAMES = [
        1 => 'январь',
        2 => 'февраль',
        3 => 'март',
        4 => 'апрель',
        5 => 'май',
        6 => 'июнь',
        7 => 'июль',
        8 => 'август',
        9 => 'сентябрь',
        10 => 'октябрь',
        11 => 'ноябрь',
        12 => 'декабрь',
    ];

    public static function getMonthName(int $month): string
    {
        if (!isset(self::NAMES[$month])) {
            throw new CoreException('Непредусмотренный месяц');
        }

        return self::NAMES[$month];
    }
}
