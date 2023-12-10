<?php

declare(strict_types=1);

namespace tests\utils;

use app\utils\NumberEnding;
use PHPUnit\Framework\TestCase;

class NumberEndingTest extends TestCase
{
    /**
     * @param int $value
     * @param string $expected
     * @dataProvider getVariants
     */
    public function testNumbers(int $value, string $expected): void
    {
        $result = NumberEnding::getNumEnding($value, ['штука', 'штуки', 'штук']);
        self::assertEquals($expected, $result);
    }

    public static function getVariants(): array
    {
        return [
            'минус сто тридцать семь' => [-137, 'штук'],
            'минус сто' => [-100, 'штук'],
            'минус десять' => [-10, 'штук'],
            'минус пять' => [-5, 'штук'],
            'минус четыре' => [-4, 'штуки'],
            'минус три' => [-3, 'штуки'],
            'минус два' => [-2, 'штуки'],
            'минус один' => [-1, 'штука'],
            'нуль' => [0, 'штук'],
            'единица' => [1, 'штука'],
            'двойка' => [2, 'штуки'],
            'тройка' => [3, 'штуки'],
            'четвёрка' => [4, 'штуки'],
            'пятёрка' => [5, 'штук'],
            'шестёрка' => [6, 'штук'],
            'семёрка' => [7, 'штук'],
            'восьмёрка' => [8, 'штук'],
            'девятка' => [9, 'штук'],
            'десятка' => [10, 'штук'],
            'одиннадцать' => [11, 'штук'],
            'двенадцать' => [12, 'штук'],
            'двадцать' => [20, 'штук'],
            'двадцать один' => [21, 'штука'],
            'сто двадцать один' => [121, 'штука'],
        ];
    }
}
