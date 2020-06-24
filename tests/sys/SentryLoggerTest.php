<?php

declare(strict_types=1);

namespace tests\sys;

use app\sys\SentryLogger;
use PHPUnit\Framework\TestCase;
use stdClass;

class SentryLoggerTest extends TestCase
{
    /**
     * @var SentryLogger
     */
    private $logger;

    public function setUp(): void
    {
        $this->logger = new SentryLogger('');
    }

    /**
     * @dataProvider getExamples
     * @param array $in
     * @param array $expected
     */
    public function testPlainContext(array $in, array $expected): void
    {
        $this->assertSame($expected, $this->logger->plainContext($in));
    }

    /**
     * @return array
     */
    public function getExamples(): array
    {
        $object = new stdClass();
        $object->prop1 = 'a';
        $object->prop2 = 17;
        $object->prop3 = false;
        $object->prop4 = true;
        $object->prop5 = null;
        $object->prop6 = 0;

        return [
            'пустой массив' => [
                [],
                []
            ],
            'плоские строки' => [
                ['a' => 'b', 'c' => null, 'd' => 0, 'e' => 0.1, 'f' => false, 'g' => true, 'z'],
                ['a' => 'b', 'c' => null, 'd' => '0', 'e' => '0.1', 'f' => false, 'g' => true, 0 => 'z'],
            ],
            'вложенные массивы' => [
                ['a' => ['b'], 'c' => [null], 'd' => [0], 'e' => [0.1]],
                ['a_0' => 'b', 'c_0' => null, 'd_0' => '0', 'e_0' => '0.1'],
            ],
            'вложенные массивы c ключами' => [
                ['a' => ['b' => 'c'], 'd' => [null], 'e' => [1 => 2], 'f' => [0.10]],
                ['a_b' => 'c', 'd_0' => null, 'e_1' => '2', 'f_0' => '0.1'],
            ],
            'вложенные массивы c пустыми объектами' => [
                ['a' => new stdClass(), 'b' => ['c' => $object]],
                ['a' => '{}', 'b_c' => '{"prop1":"a","prop2":17,"prop3":false,"prop4":true,"prop5":null,"prop6":0}'],
            ],
        ];
    }
}
