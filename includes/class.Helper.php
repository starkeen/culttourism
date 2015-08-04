<?php

class Helper {

    public static function autoloader($className) {
        $path1 = _DIR_INCLUDES . "/class.$className.php";
        $path2 = _DIR_MODELS . "/class.$className.php";
        if (file_exists($path1)) {
            include $path1;
        } elseif (file_exists($path2)) {
            include $path2;
        }
        return false;
    }

    /**
     * Функция возвращает окончание для множественного числа слова на основании числа и массива окончаний
     * @param  $number Integer Число на основе которого нужно сформировать окончание
     * @param  $endingsArray  Array Массив слов или окончаний для чисел (1, 4, 5),
     *         например array('яблоко', 'яблока', 'яблок')
     * @return String
     */
    public static function getNumEnding($number, $endingArray) {
        $number = $number % 100;
        if ($number >= 11 && $number <= 19) {
            $ending = $endingArray[2];
        } else {
            $i = $number % 10;
            switch ($i) {
                case (1): $ending = $endingArray[0];
                    break;
                case (2):
                case (3):
                case (4): $ending = $endingArray[1];
                    break;
                default: $ending = $endingArray[2];
            }
        }
        return $ending;
    }

    /**
     * Функция возвращает транслит строки
     * @param  $text String Строка, подлежащая транслитерации
     * @param  $space String Строка-заменитель пробелов
     * @return String
     */
    public static function getTranslit($text, $space = ' ') {
        $transtable = array(
            'а' => 'a', 'А' => 'A',
            'б' => 'b', 'Б' => 'B',
            'в' => 'v', 'В' => 'V',
            'г' => 'g', 'Г' => 'G',
            'д' => 'd', 'Д' => 'D',
            'е' => 'e', 'Е' => 'E',
            'ё' => 'yo', 'Ё' => 'Yo',
            'ж' => 'zh', 'Ж' => 'Zh',
            'з' => 'z', 'З' => 'Z',
            'и' => 'i', 'И' => 'I',
            'й' => 'y', 'Й' => 'Y',
            'к' => 'k', 'К' => 'K',
            'л' => 'l', 'Л' => 'L',
            'м' => 'm', 'М' => 'M',
            'н' => 'n', 'Н' => 'N',
            'о' => 'o', 'О' => 'O',
            'п' => 'p', 'П' => 'P',
            'р' => 'r', 'Р' => 'R',
            'с' => 's', 'С' => 'S',
            'т' => 't', 'Т' => 'T',
            'у' => 'u', 'У' => 'U',
            'ф' => 'f', 'Ф' => 'F',
            'х' => 'h', 'Х' => 'H',
            'ц' => 'ts', 'Ц' => 'Ts',
            'ч' => 'ch', 'Ч' => 'Ch',
            'ш' => 'sh', 'Ш' => 'Sh',
            'щ' => 'sсh', 'Щ' => 'Sch',
            'ъ' => '\'', 'Ъ' => '\'',
            'ы' => 'y', 'Ы' => 'Y',
            'ь' => '', 'Ь' => '',
            'э' => 'e', 'Э' => 'E',
            'ю' => 'yu', 'Ю' => 'Yu',
            'я' => 'ya', 'Я' => 'Ya',
            ' ' => $space
        );
        return strtr($text, $transtable);
    }

    /**
     * Функция возвращает замененные парные символы по клавиатуре QWERTY - ЙЦУКЕН
     * @param  $text String Строка, подлежащая преобразованию
     * @param  $latrus Boolean Флаг направления преобразования
     * @return String
     */
    public static function getQwerty($text, $latrus = true) {
        $transtable = array(
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
        );
        foreach ($transtable as $k => $v) {
            $transtable[mb_strtoupper($k, 'UTF-8')] = mb_strtoupper($v, 'UTF-8');
        }
        if (!$latrus) {
            $transtable = array_flip($transtable);
        }
        return strtr($text, $transtable);
    }

    /**
     * Функция возвращает случайный уникальный идентификатор
     * @return String
     */
    public static function getGUID() {
        mt_srand((double) microtime() * 1000000);
        return(md5(uniqid(mt_rand(), 1)));
    }

    /**
     * Функция вычисляет расстояние между двумя точками по координатам
     * 
     * @param float $lat1
     * @param float $long1
     * @param float $lat2
     * @param float $long2
     * @return float
     */
    
    public static function distanceGPS($lat1, $long1, $lat2, $long2) {
        $R = 6372795; // радиус Земли
        //перевод коордитат в радианы
        $lat1 *= pi() / 180;
        $lat2 *= pi() / 180;
        $long1 *= pi() / 180;
        $long2 *= pi() / 180;

        //вычисление косинусов и синусов широт и разницы долгот
        $cl1 = cos($lat1);
        $cl2 = cos($lat2);
        $sl1 = sin($lat1);
        $sl2 = sin($lat2);
        $delta = $long2 - $long1;
        $cdelta = cos($delta);
        $sdelta = sin($delta);

        //вычисления длины большого круга
        $y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
        $x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;
        $ad = atan2($y, $x);
        $dist = $ad * $R; //расстояние между двумя координатами в метрах

        return $dist;
    }

}
