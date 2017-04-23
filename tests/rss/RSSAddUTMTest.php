<?php

use PHPUnit\Framework\TestCase;

class RSSAddUTMTest extends TestCase
{
    /** @var IRSSGenerator */
    private $generator;

    public function setUp()
    {
        $this->generator = $this->getMockBuilder(IRSSGenerator::class)->getMock();
        $this->generator->method('process')
                        ->willReturnCallback(
                            function ($arg) {
                                $out = [];
                                foreach ($arg as $item) {
                                    $out[] = [
                                        'title' => $item['title'],
                                        'text' => $item['text'],
                                    ];
                                }
                                return $out;
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
                    [
                        'title' => 'title 1',
                        'text' => 'text 1',
                    ],
                    [
                        'title' => 'title 2',
                        'text' => 'text 2',
                    ],
                ],
                [
                    [
                        'title' => 'title 1',
                        'text' => 'text 1',
                    ],
                    [
                        'title' => 'title 2',
                        'text' => 'text 2',
                    ],
                ],
            ],
        ];
    }
}