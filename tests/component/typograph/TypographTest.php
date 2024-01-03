<?php

declare(strict_types=1);

namespace tests\component\typograph;

use app\component\typograph\Typograph;
use JoliTypo\Fixer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TypographTest extends TestCase
{
    private MockObject|null|Fixer $baseFixerMock = null;

    private ?Typograph $service = null;

    public function setUp(): void
    {
        $this->baseFixerMock = $this->getMockBuilder(Fixer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fix', 'setLocale', 'setRules'])
            ->getMock();

        $this->service = new Typograph($this->baseFixerMock);
    }

    /**
     * @dataProvider getExamples
     *
     * @param string $input
     * @param string $output
     */
    public function testPostProcessing(string $input, string $output): void
    {
        $this->baseFixerMock->expects(self::once())
            ->method('fix')
            ->willReturn($input);

        self::assertEquals($output, $this->service->typo($input));
    }

    public static function getExamples(): array
    {
        return [
            'пустая строка' => ['', ''],
            'разрыв в веках' => ['середина <nobr>XIX вв.</nobr>ека', 'середина XIX века'],
            'строка с тегами nobr' => ['тут начало <nobr>середина сроки </nobr>и её конец', 'тут начало середина сроки и её конец'],
        ];
    }
}
