<?php

declare(strict_types=1);

namespace tests\utils;

use app\utils\Keyboard;
use PHPUnit\Framework\TestCase;

class KeyboardTest extends TestCase
{
    private const INCOMING_STRING = 'a\s;d10-=.,/[]z()йзбю.жэхъ';

    public function testTransformationEmptyString(): void
    {
        self::assertEquals('', Keyboard::getQwerty('', true));
    }

    public function testTransformationLatRus(): void
    {
        self::assertEquals('ф\ыЖв10-=ЮБ.ХЪя()йзбюЮжэхъ', Keyboard::getQwerty(self::INCOMING_STRING, true));
    }

    public function testTransformationRusLat(): void
    {
        self::assertEquals('a\s;d10-=/,/[]z()qpбю/жэхъ', Keyboard::getQwerty(self::INCOMING_STRING, false));
    }
}
