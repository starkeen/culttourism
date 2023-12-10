<?php

declare(strict_types=1);

namespace tests\utils;

use app\utils\Dates;
use PHPUnit\Framework\TestCase;

class DatesTest extends TestCase
{
    /**
     * @dataProvider getExamples
     * @param string $in
     * @param string|null $expected
     */
    public function testPlainContext(string $in, ?string $expected): void
    {
        $this->assertSame($expected, Dates::normalToSQL($in));
    }

    public static function getExamples(): array
    {
        return [
            'пустая дата' => ['', null],
            'дата с длинным годом' => ['15.12.2023', '2023-12-15'],
            'дата с длинным годом в XX веке' => ['15.12.1923', '1923-12-15'],
            'дата с коротким годом в XX веке' => ['15.12.98', '1998-12-15'],
            'день без ведущего нуля' => ['5.07.98', '1998-07-05'],
            'месяц без ведущего нуля' => ['05.7.98', '1998-07-05'],
            'неправильная дата по формату со слешом' => ['01/12/2021', '2021-01-12'],
            'неправильная дата по формату с дефисом' => ['01-03-21', '2001-03-21'],
            'неправильная дата по дню' => ['32.01.2021', null],
            'неправильная дата по месяцу' => ['25.13.2021', null],
        ];
    }
}
