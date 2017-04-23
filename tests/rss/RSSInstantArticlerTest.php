<?php

use PHPUnit\Framework\TestCase;

class RSSInstantArticlerTest extends TestCase
{
    /** @var IRSSGenerator */
    private $generator;

    public function setUp()
    {
        $this->generator = $this->getMockBuilder(IRSSGenerator::class)->getMock();
        $this->generator->method('process')
                        ->willReturnCallback(
                            function ($arg) {
                                return [
                                    'title' => $arg['title'],
                                    'text' => $arg['text'],
                                ];
                            }
                        );
    }

    /**
     * @dataProvider getExamples
     */
    public function testProcessing($in, $expected)
    {
        $articler = new RSSInstantArticler($this->generator);

        $out = $articler->process($in);

        $this->assertEquals($expected, $out);
    }

    /**
     * @return array
     */
    public function getExamples()
    {
        return [
            [
                [
                    'title' => 'title 1',
                    'text' => 'text 1',
                ],
                [
                    'title' => 'title 1',
                    'text' => 'text 1',
                ],
            ],
        ];
    }
}