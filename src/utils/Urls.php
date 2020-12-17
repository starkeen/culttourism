<?php

declare(strict_types=1);

namespace app\utils;

class Urls
{
    /**
     * Из относительного пути делаем абсолютный
     * @param string $path
     *
     * @return string
     */
    public static function getAbsoluteURL(string $path): string
    {
        return strpos($path, '/') === 0 ? rtrim(_SITE_URL, '/') . $path : $path;
    }
}
