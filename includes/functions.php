<?php

if (!function_exists('getallheaders')) {

    function getallheaders() {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

}

function getGUID() {
    mt_srand((double) microtime() * 1000000);
    return(md5(uniqid(mt_rand(), 1)));
}

function cut_trash_int($data) {
    return intval($data);
}

function cut_trash_string($data) {
    $string = (string) $data;
    return trim($string);
}

function cut_trash_word($data) {
    $string = (string) $data;
    return trim($string);
}

function cut_trash_text($data) {
    $text = trim(strip_tags($data, '<b><i><strong><em>'));
    return $text;
}

function cut_trash_html($data) {
    $text = trim($data);
    return (string) $text;
}

function cut_trash_float($data) {
    $text = str_replace(',', '.', trim($data));
    return floatval($text);
}

function normSQLdate($dateSQL) {
    //приводит дату из вида "2008-02-15 22:03:43" к нормальному
    return substr($dateSQL, 8, 2) . "." . substr($dateSQL, 5, 2) . "." . substr($dateSQL, 0, 4);
}

function transSQLdate($date) {
    //приводит дату к виду "2008-02-15" из нормального
    list($d, $m, $y) = explode('.', $date);
    if (strlen($y) == 2) {
        $y = "20$y";
    }
    return "$y-$m-$d";
}

function substr_x($str, $start, $length = null, $encoding = null) {
    if (function_exists(mb_substr)) {
        return mb_substr($str, $start, $length, $encoding);
    } else {
        return substr($str, $start, $length);
    }
}

function translit($word, $space = ' ') {
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
        'ц' => 'c', 'Ц' => 'C',
        'ч' => 'ch', 'Ч' => 'Ch',
        'ш' => 'sh', 'Ш' => 'Sh',
        'щ' => 'sсh', 'Щ' => 'Sch',
        'ъ' => '\'', 'Ъ' => '\'',
        'ы' => 'y', 'Ы' => 'Y',
        //'ь' => '\'', 'Ь' => '\'',
        'ь' => '', 'Ь' => '',
        'э' => 'e', 'Э' => 'E',
        'ю' => 'yu', 'Ю' => 'Yu',
        'я' => 'ya', 'Я' => 'Ya',
        ' ' => $space
    );
    $st = strtr($word, $transtable);
    return $st;
}

?>