<?php

declare(strict_types=1);

namespace app\utils;

class NumberEnding
{
    /**
     * Функция возвращает окончание для множественного числа слова на основании числа и массива окончаний
     *
     * @param  int      $number      Число, на основе которого нужно  сформировать окончание
     * @param  string[] $endingArray Массив слов или окончаний для чисел (1, 4, 5),
     *                                  например, array('яблоко', 'яблока', 'яблок')
     * @return string
     */
    public static function getNumEnding(int $number, array $endingArray): string
    {
        $number %= 100;
        if ($number < 0) {
            $number = -1 * $number;
        }
        if ($number >= 11 && $number <= 19) {
            $ending = $endingArray[2];
        } else {
            $factor = $number % 10;
            $ending = match ($factor) {
                1 => $endingArray[0],
                2, 3, 4 => $endingArray[1],
                default => $endingArray[2],
            };
        }

        return $ending;
    }
}
