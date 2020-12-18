<?php

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }
}

function getGUID(): string
{
    mt_srand((double) microtime() * 1000000);

    return (md5(uniqid(mt_rand(), 1)));
}

function cut_trash_int($data): int
{
    return (int) $data;
}

function cut_trash_string($data): string
{
    return (trim((string) $data));
}

function cut_trash_word($data): string
{
    return cut_trash_string($data);
}

function cut_trash_text($data): string
{
    $text = trim(strip_tags($data));

    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}

function cut_trash_html($data): string
{
    $text = trim($data);

    return ($text);
}

function cut_trash_float($data): float
{
    $text = str_replace(',', '.', trim($data));

    return (float) $text;
}

function transSQLdate($date)
{
    //приводит дату к виду "2008-02-15" из нормального
    [$d, $m, $y] = explode('.', $date);
    if (strlen($y) == 2) {
        $y = "20$y";
    }

    return "$y-$m-$d";
}

/**
 * @param        $word
 * @param string $space
 * @return string
 */
function translit($word, $space = ' '): string
{
    $transtable = [
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
        //'ь' => '\'', 'Ь' => '\'',
        'ь' => '',
        'Ь' => '',
        'э' => 'e',
        'Э' => 'E',
        'ю' => 'yu',
        'Ю' => 'Yu',
        'я' => 'ya',
        'Я' => 'Ya',
        ' ' => $space,
    ];

    return strtr($word, $transtable);
}
