<?php

declare(strict_types=1);

namespace app\utils;

class Strings
{
    private const PAIRS = [
        'а' => 'a',
        'А' => 'A',
        'б' => 'b',
        'Б' => 'B',
        'в' => 'v',
        'В' => 'V',
        'г' => 'g',
        'Г' => 'G',
        'д' => 'd',
        'Д' => 'D',
        'е' => 'e',
        'Е' => 'E',
        'ё' => 'yo',
        'Ё' => 'Yo',
        'ж' => 'zh',
        'Ж' => 'Zh',
        'з' => 'z',
        'З' => 'Z',
        'и' => 'i',
        'И' => 'I',
        'й' => 'y',
        'Й' => 'Y',
        'к' => 'k',
        'К' => 'K',
        'л' => 'l',
        'Л' => 'L',
        'м' => 'm',
        'М' => 'M',
        'н' => 'n',
        'Н' => 'N',
        'о' => 'o',
        'О' => 'O',
        'п' => 'p',
        'П' => 'P',
        'р' => 'r',
        'Р' => 'R',
        'с' => 's',
        'С' => 'S',
        'т' => 't',
        'Т' => 'T',
        'у' => 'u',
        'У' => 'U',
        'ф' => 'f',
        'Ф' => 'F',
        'х' => 'h',
        'Х' => 'H',
        'ц' => 'ts',
        'Ц' => 'Ts',
        'ч' => 'ch',
        'Ч' => 'Ch',
        'ш' => 'sh',
        'Ш' => 'Sh',
        'щ' => 'sсh',
        'Щ' => 'Sch',
        'ъ' => '\'',
        'Ъ' => '\'',
        'ы' => 'y',
        'Ы' => 'Y',
        'ь' => '',
        // 'Ь' => '',
        'э' => 'e',
        'Э' => 'E',
        'ю' => 'yu',
        'Ю' => 'Yu',
        'я' => 'ya',
        'Я' => 'Ya',
    ];

    /**
     * Функция возвращает транслит строки
     *
     * @param  string $text  Строка,
     *                       подлежащая
     *                       транслитерации
     * @param  string $space Строка-заменитель пробелов
     * @return string
     */
    public static function getTransliteration(string $text, string $space = ' '): string
    {
        $replacePairs = self::PAIRS;
        $replacePairs[' '] = $space;

        return strtr($text, $replacePairs);
    }
}
