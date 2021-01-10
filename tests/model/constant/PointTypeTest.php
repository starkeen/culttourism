<?php

declare(strict_types=1);

namespace tests\model\constant;

use app\model\constant\PointType;
use PHPUnit\Framework\TestCase;

class PointTypeTest extends TestCase
{
    public function testStructure(): void
    {
        self::assertCount(9, PointType::values());
        self::assertCount(6, PointType::getSights());
        self::assertCount(3, PointType::getServices());
    }

    public function testIcon(): void
    {
        self::assertEquals('camera.png', (new PointType(2))->getIcon());
    }

    public function testFullName(): void
    {
        self::assertEquals('церкви и монастыри', (new PointType(3))->getFullName());
    }

    public function testShortName(): void
    {
        self::assertEquals('музеи', (new PointType(4))->getShortName());
    }

    public function testIsSight(): void
    {
        self::assertTrue((new PointType(5))->isSight());
        self::assertFalse((new PointType(7))->isSight());
    }
}
