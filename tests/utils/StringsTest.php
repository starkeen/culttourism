<?php

declare(strict_types=1);

namespace tests\utils;

use app\utils\Strings;
use PHPUnit\Framework\TestCase;

class StringsTest extends TestCase
{
    private const INCOMING_STRING = 'a\s;d10-=.,/[]z()йзбю.жэхъёЁьЬъЪщ';

    public function testTransliterationEmptyString(): void
    {
        self::assertEquals('', Strings::getTransliteration(''));
    }

    public function testTransliterationCustomSpace(): void
    {
        self::assertEquals('ab__vg', Strings::getTransliteration('аб вг', '__'));
    }

    public function testTransformation(): void
    {
        self::assertEquals('a\s;d10-=.,/[]z()yzbyu.zheh\'yoYoЬ\'\'sсh', Strings::getTransliteration(self::INCOMING_STRING));
    }
}
