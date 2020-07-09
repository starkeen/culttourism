<?php

declare(strict_types=1);

namespace tests\component\typograph;

use app\component\typograph\Typograph;
use EMTypograph;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TypographIntegrationTest extends TestCase
{
    /**
     * @var Typograph
     */
    private $service;

    public function setUp(): void
    {
        $this->service = new Typograph(new EMTypograph());
    }

    /**
     * @dataProvider getExamples
     *
     * @param string $input
     * @param string $output
     */
    public function testProcessing(string $input, string $output): void
    {
        self::assertEquals($output, $this->service->typo($input));
    }

    public function getExamples(): array
    {
        return [
            'номер телефона - 1' => [
                '+79111234567',
                '+79 111 234 567',
            ],
            'номер телефона - 2' => [
                '+7 (911) 123-45-67',
                '+7 (911) 123−45−67',
            ],
            'номер телефона - 3' => [
                '(911) 123-45-67',
                '(911) 123−45−67',
            ],
            'номер телефона с восьмёркой - 1' => [
                '89613775417',
                '89 613 775 417',
            ],
            'номер телефона с восьмёркой - 2' => [
                '8 (961) 3775417',
                '8 (961) 3 775 417',
            ],
            'номер телефона с восьмёркой - 3' => [
                '8 (961) 377-54-17',
                '8 (961) 377−54−17',
            ],
            'номер телефона короткий - 1' => [
                '8 (12345) 75-4-17',
                '8 (12 345) 75−4-17',
            ],
            'номер телефона короткий - 2' => [
                '(12345) 75-4-17',
                '(12 345) 75−4-17',
            ],
            'номер телефона средний - 1' => [
                '(1234) 12-13-14',
                '(1234) 12−13−14',
            ],
            'номер телефона средний - 2' => [
                '8 (1234) 12-13-14',
                '8 (1234) 12−13−14',
            ],
            'номер телефона средний - 3' => [
                '+7 (1234) 12-13-14',
                '+7 (1234) 12−13−14',
            ],
            'номер телефона средний - 4' => [
                '81234 12-13-14',
                '81 234 12−13−14',
            ],
            'строка в кавычках' => [
                'текст "в кавычках" и без',
                'текст «в кавычках» и без',
            ],
            'строка в одинарных кавычках' => [
                'текст \'в кавычках\' и без',
                'текст \'в кавычках\' и без',
            ],
        ];
    }
}
