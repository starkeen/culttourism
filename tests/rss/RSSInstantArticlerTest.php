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
                        'text' => '<p>Обычный текст</p>',
                    ],
                    [
                        'title' => 'title 2',
                        'text' => '
                            <p style="color:red;">текст</p>
                            <p style="margin: 2px;">
                                <a href="http://ya.ru/">
                                    <img src="image.jpg" style="width: 50px;" />
                                </a>
                            </p>
                            <p style="font-size: 12px;"><a href="#">снова</a> текст</p>
                        ',
                    ],
                ],
                [
                    [
                        'title' => 'title 1',
                        'text' => '<p>Обычный текст</p>',
                    ],
                    [
                        'title' => 'title 2',
                        'text' => '
                            <p style="color:red;">текст</p>
                            
                                <a href="http://ya.ru/">
                                    <img src="image.jpg" style="width: 50px;" />
                                </a>
                            
                            <p style="font-size: 12px;"><a href="#">снова</a> текст</p>
                        ',
                    ],
                ],
            ],
        ];
    }
}