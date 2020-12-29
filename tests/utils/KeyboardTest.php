<?php

declare(strict_types=1);

namespace tests\utils;

use app\utils\Keyboard;
use PHPUnit\Framework\TestCase;

class KeyboardTest extends TestCase
{
    private const INCOMING_STRING = 'a\s;d1`0-=.,/[]z()йзбю.жэхъё';

    public function testTransformationEmptyString(): void
    {
        self::assertEquals('', Keyboard::getQwerty('', true));
    }

    public function testTransformationLatRus(): void
    {
        self::assertEquals('ф\ыЖв1`0-=ЮБ.ХЪя()йзбюЮжэхъё', Keyboard::getQwerty(self::INCOMING_STRING, true));
    }

    public function testTransformationRusLat(): void
    {
        self::assertEquals('a\s;d1`0-=/,/[]z()qpбю/жэхъё', Keyboard::getQwerty(self::INCOMING_STRING, false));
    }
}
