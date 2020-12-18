<?php

declare(strict_types=1);

namespace app\utils;

use DateTime;

class Dates
{
    /**
     * приводит дату к виду "2008-02-15" из нормального 15.02.2008
     *
     * @param string $date
     * @return string|null
     */
    public static function normalToSQL(string $date): ?string
    {
        $timestamp = strtotime($date);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
}
