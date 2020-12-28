<?php

declare(strict_types=1);

namespace tests\utils;

use app\utils\GPS;
use PHPUnit\Framework\TestCase;

class GPSTest extends TestCase
{
    public function testCalculating(): void
    {
        $latitude1 = 12.34;
        $longitude1 = 11.22;
        $latitude2 = 56.78;
        $longitude2 = 33.44;

        $distance = GPS::distanceGPS($latitude1, $longitude1, $latitude2, $longitude2);

        self::assertEquals(5294912, $distance);
    }

    public function testNegativeCalculating(): void
    {
        $latitude1 = -12.34;
        $longitude1 = 11.22;
        $latitude2 = 56.78;
        $longitude2 = -33.44;

        $distance = GPS::distanceGPS($latitude1, $longitude1, $latitude2, $longitude2);

        self::assertEquals(8714822, $distance);
    }
}
