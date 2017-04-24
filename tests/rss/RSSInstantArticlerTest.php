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
                                        'br_text_absolute' => $item['br_text_absolute'],
                                        'br_text' => $item['br_text'],
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
                        'br_text' => '<p>Обычный текст</p>',
                        'br_text_absolute' => '<p>Обычный текст</p>',
                    ],
                    [
                        'title' => 'title 2',
                        'br_text' => '
                            <p style="color:red;">текст</p>
                            <p style="margin: 2px;">
                                <a href="http://ya.ru/">
                                    <img src="image.jpg" style="width: 50px;" />
                                </a>
                            </p>
                            <p style="font-size: 12px;"><a href="#">снова</a> текст</p>
                        ',
                        'br_text_absolute' => '',
                    ],
                ],
                [
                    [
                        'title' => 'title 1',
                        'br_text' => '<p>Обычный текст</p>',
                        'br_text_absolute' => '<p>Обычный текст</p>',
                    ],
                    [
                        'title' => 'title 2',
                        'br_text' => '
                            <p style="color:red;">текст</p>
                            
                                <a href="http://ya.ru/">
                                    <img src="image.jpg" style="width: 50px;" />
                                </a>
                            
                            <p style="font-size: 12px;"><a href="#">снова</a> текст</p>
                        ',
                        'br_text_absolute' => '',
                    ],
                ],
            ],
        ];
    }
}