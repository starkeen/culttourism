<?php

class Helper
{
    /**
     * Функция возвращает транслит строки
     * @param string $text Строка, подлежащая транслитерации
     * @param string $space Строка-заменитель пробелов
     * @return string
     */
    public static function getTransliteration(string $text, string $space = ' '): string
    {
        $replacePairs = [
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
            ' ' => $space,
        ];

        return strtr($text, $replacePairs);
    }

    /**
     * Функция возвращает замененные парные символы по клавиатуре QWERTY - ЙЦУКЕН
     * @param string $text Строка, подлежащая преобразованию
     * @param bool $latrus Флаг направления преобразования
     * @return string
     */
    public static function getQwerty(string $text, $latrus = true): string
    {
        $replacePairs = [
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
        foreach ($replacePairs as $k => $v) {
            $replacePairs[mb_strtoupper($k, 'UTF-8')] = mb_strtoupper($v, 'UTF-8');
        }
        if (!$latrus) {
            $replacePairs = array_flip($replacePairs);
        }

        return strtr($text, $replacePairs);
    }
}
