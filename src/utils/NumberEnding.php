<?php

declare(strict_types=1);

namespace app\utils;

class NumberEnding
{
    /**
     * Функция возвращает окончание для множественного числа слова на основании числа и массива окончаний
     * @param int $number Число на основе которого нужно сформировать окончание
     * @param string[] $endingArray Массив слов или окончаний для чисел (1, 4, 5), например array('яблоко', 'яблока', 'яблок')
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
            $i = $number % 10;
            switch ($i) {
                case (1):
                    $ending = $endingArray[0];
                    break;
                case (2):
                case (3):
                case (4):
                    $ending = $endingArray[1];
                    break;
                default:
                    $ending = $endingArray[2];
            }
        }

        return $ending;
    }
}
