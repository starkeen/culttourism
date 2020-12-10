<?php

declare(strict_types=1);

namespace tests\component\typograph;

use app\component\typograph\Typograph;
use EMT\EMTypograph;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TypographTest extends TestCase
{
    /**
     * @var MockObject|EMTypograph
     */
    private $baseTypographMock;

    /**
     * @var Typograph
     */
    private $service;

    public function setUp(): void
    {
        $this->baseTypographMock = $this->getMockBuilder(EMTypograph::class)
                                  ->disableOriginalConstructor()
                                  ->onlyMethods(['setup', 'apply', 'set_text'])
                                  ->getMock();

        $this->service = new Typograph($this->baseTypographMock);
    }

    /**
     * @dataProvider getExamples
     *
     * @param string $input
     * @param string $output
     */
    public function testPostProcessing(string $input, string $output): void
    {
        $this->baseTypographMock->expects(self::once())
                          ->method('apply')
                          ->willReturn($input);

        self::assertEquals($output, $this->service->typo($input));
    }

    public function getExamples(): array
    {
        return [
            'пустая строка' => ['', ''],
            'разрыв в веках' => ['середина <nobr>XIX вв.</nobr>ека', 'середина XIX века'],
            'строка с тегами nobr' => ['тут начало <nobr>середина сроки </nobr>и её конец', 'тут начало середина сроки и её конец'],
        ];
    }
}
