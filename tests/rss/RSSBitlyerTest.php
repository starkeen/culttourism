<?php

use PHPUnit\Framework\TestCase;

class RSSBitlyerTest extends TestCase
{
    /** @var IRSSGenerator */
    private $generator;

    /** @var Bitly|PHPUnit_Framework_MockObject_MockObject */
    private $bitly;

    public function setUp()
    {
        $this->generator = $this->getMockBuilder(IRSSGenerator::class)->getMock();
        $this->generator->method('process')
                        ->willReturnCallback(
                            function ($arg) {
                                $out = [];
                                foreach ($arg as $item) {
                                    $out[] = [
                                        'br_title' => $item['br_title'],
                                        'br_text' => $item['br_text'],
                                        'br_text_absolute' => $item['br_text_absolute'],
                                    ];
                                }
                                return $out;
                            }
                        );

        $this->bitly = $this->getMockBuilder(Bitly::class)->disableOriginalConstructor()->getMock();
        $this->bitly->method('short')->willReturnCallback(function ($arg) {
            return sprintf('https://%s.tld/', md5($arg));
        });
    }

    /**
     * @param array $in
     * @param string $expected
     * @param int $count
     * @dataProvider getExamples
     */
    public function testProcessing($in, $expected, $count)
    {
        $this->bitly->expects($this->exactly($count))->method('short');

        $bitlyer = new RSSBitlyer($this->generator, $this->bitly);
        $bitlyer->rootUrl = 'https://host.tld/';
        $out = $bitlyer->process($in);

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
                        'br_title' => 'link without slash',
                        'br_text' => '<p>before <a href="https://host.tld">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld" target="_blank">link</a> after</p>',
                    ],
                ],
                [
                    [
                        'br_title' => 'link without slash',
                        'br_text' => '<p>before <a href="https://host.tld">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld" target="_blank">link</a> after</p>',
                    ],
                ],
                0,
            ],
            [
                [
                    [
                        'br_title' => 'link with slash',
                        'br_text' => '<p>before <a href="https://host.tld/">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/" target="_blank">link</a> after</p>',
                    ],
                ],
                [
                    [
                        'br_title' => 'link with slash',
                        'br_text' => '<p>before <a href="https://host.tld/">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://6b94df3c128d0d2be90ae7e67a91bd9e.tld/" target="_blank">link</a> after</p>',
                    ],
                ],
                1,
            ],
        ];
    }
}