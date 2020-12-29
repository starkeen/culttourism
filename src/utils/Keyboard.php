<?php

declare(strict_types=1);

namespace app\utils;

class Keyboard
{
    private const PAIRS = [
        'q' => 'й',
        'w' => 'ц',
        'e' => 'у',
        'r' => 'к',
        't' => 'е',
        'y' => 'н',
        'u' => 'г',
        'i' => 'ш',
        'o' => 'щ',
        'p' => 'з',
        '[' => 'х',
        ']' => 'ъ',
        //
        'a' => 'ф',
        's' => 'ы',
        'd' => 'в',
        'f' => 'а',
        'g' => 'п',
        'h' => 'р',
        'j' => 'о',
        'k' => 'л',
        'l' => 'д',
        ';' => 'ж',
        '\'' => 'э',
        //
        'z' => 'я',
        'x' => 'ч',
        'c' => 'с',
        'v' => 'м',
        'b' => 'и',
        'n' => 'т',
        'm' => 'ь',
        ',' => 'б',
        '.' => 'ю',
        '/' => '.',
    ];

    /**
     * Функция возвращает замененные парные символы по клавиатуре QWERTY - ЙЦУКЕН
     * @param string $text Строка, подлежащая преобразованию
     * @param bool $latRusDirection Флаг направления преобразования
     * @return string
     */
    public static function getQwerty(string $text, $latRusDirection = true): string
    {
        return strtr($text, self::getPairs($latRusDirection));
    }

    /**
     * @param bool $latRusDirection
     * @return array
     */
    private static function getPairs(bool $latRusDirection): array
    {
        $result = self::PAIRS;

        foreach (self::PAIRS as $k => $v) {
            $result[mb_strtoupper($k, 'UTF-8')] = mb_strtoupper($v, 'UTF-8');
        }
        if (!$latRusDirection) {
            $result = array_flip($result);
        }

        return $result;
    }
}
