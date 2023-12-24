<?php

declare(strict_types=1);

namespace app\utils;

class GPS
{
    private const R = 6372795; // радиус Земли

    /**
     * Функция вычисляет расстояние между двумя точками по координатам
     *
     * @param  float $lat1
     * @param  float $long1
     * @param  float $lat2
     * @param  float $long2
     * @return int
     */
    public static function distanceGPS(float $lat1, float $long1, float $lat2, float $long2): int
    {
        //перевод координат в радианы
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
        $y = sqrt((($cl2 * $sdelta) ** 2) + (($cl1 * $sl2 - $sl1 * $cl2 * $cdelta) ** 2));
        $x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;
        $ad = atan2($y, $x);

        return (int) round($ad * self::R);
    }
}
