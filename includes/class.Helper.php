<?php

class Helper {

    public static function autoloader($className) {
        $path1 = _DIR_INCLUDES . "/class.$className.php";
        $path2 = _DIR_MODELS . "/class.$className.php";
        if (file_exists($path1))
            include $path1;
        elseif (file_exists($path2))
            include $path2;
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

}

?>
