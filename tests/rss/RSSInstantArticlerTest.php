<?php

namespace tests\rss;

use app\rss\IRSSGenerator;
use app\rss\RSSInstantArticler;
use PHPUnit\Framework\TestCase;

class RSSInstantArticlerTest extends TestCase
{
    /** @var IRSSGenerator */
    private $generator;

    public function setUp(): void
    {
        $this->generator = $this->getMockBuilder(IRSSGenerator::class)->getMock();
        $this->generator->method('process')
                        ->willReturnCallback(
                            function ($arg) {
                                $out = [];
                                foreach ($arg as $item) {
                                    $out[] = implode(PHP_EOL, [
                                        'title' => $item['title'],
                                        'br_text_absolute' => $item['br_text_absolute'],
                                        'br_text' => $item['br_text'],
                                    ]);
                                }
                                return implode(PHP_EOL, $out);
                            }
                        );
    }

    /**
     * @dataProvider getExamples
     * @param array $in
     * @param string $expected
     */
    public function testProcessing(array $in, string $expected)
    {
        $articler = new RSSInstantArticler($this->generator);

        $out = $articler->process($in);

        $this->assertEquals($expected, $out);
    }

    /**
     * @return array
     */
    public static function getExamples(): array
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
                'title 1' . PHP_EOL
                . '<p>Обычный текст</p>' . PHP_EOL
                . '<p>Обычный текст</p>' . PHP_EOL
                . 'title 2' . PHP_EOL
                 . PHP_EOL
                . '
                            <p style="color:red;">текст</p>
                            
                                <a href="http://ya.ru/">
                                    <img src="image.jpg" style="width: 50px;" />
                                </a>
                            
                            <p style="font-size: 12px;"><a href="#">снова</a> текст</p>
                        ',
            ],
        ];
    }
}
