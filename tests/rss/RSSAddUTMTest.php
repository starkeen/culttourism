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
                                        'br_title' => $item['br_title'],
                                        'br_text' => $item['br_text'],
                                        'br_text_absolute' => $item['br_text_absolute'],
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
                        'br_title' => 'link without slash',
                        'br_text' => '<p>before <a href="https://host.tld">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld" target="_blank">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link with slash',
                        'br_text' => '<p>before <a href="https://host.tld/path/">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link in second paragraph',
                        'br_text' => '
                            <p>first</p>
                            <p>
                                before
                                <a href="https://host.tld/path/">link</a>
                                 after
                            </p>
                            
                            <p>last</p>
                        ',
                        'br_text_absolute' => '
                            <p>first</p>
                            <p>
                                before
                                <a href="https://host.tld/path/">link</a>
                                 after
                            </p>
                            
                            <p>last</p>
                        ',
                    ],
                    [
                        'br_title' => 'link with page in second paragraph',
                        'br_text' => '
                            <p>first</p>
                            <p>
                                before
                                <a href="https://host.tld/path/page.html">link</a>
                                 after
                            </p>
                            
                            <p>last</p>
                        ',
                        'br_text_absolute' => '
                            <p>first</p>
                            <p>
                                before
                                <a href="https://host.tld/path/page.html">link</a>
                                 after
                            </p>
                            
                            <p>last</p>
                        ',
                    ],
                    [
                        'br_title' => 'link with slash and query',
                        'br_text' => '<p>before <a href="https://host.tld/path/?a=1&b[]=2&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/?a=1&b[]=2&c">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link with page and query',
                        'br_text' => '<p>before <a href="https://host.tld/path/page.html?a=1&b[]=2&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/page.html?a=1&b[]=2&c">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link with page and encoded query',
                        'br_text' => '<p>before <a href="https://host.tld/path/page.html?a=1&geo%5Bt%5D%5B14%5D=14&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/page.html?a=1&geo%5Bt%5D%5B14%5D=14&c">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link with page and encoded cyrillic query',
                        'br_text' => '<p>before <a href="https://host.tld/path/page.html?a=1&key=%D1%81%D0%BB%D0%BE%D0%B2%D0%BE&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/page.html?a=1&key=%D1%81%D0%BB%D0%BE%D0%B2%D0%BE&c">link</a> after</p>',
                    ],
                ],
                [
                    [
                        'br_title' => 'link without slash',
                        'br_text' => '<p>before <a href="https://host.tld">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld" target="_blank">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link with slash',
                        'br_text' => '<p>before <a href="https://host.tld/path/">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link in second paragraph',
                        'br_text' => '
                            <p>first</p>
                            <p>
                                before
                                <a href="https://host.tld/path/">link</a>
                                 after
                            </p>
                            
                            <p>last</p>
                        ',
                        'br_text_absolute' => '
                            <p>first</p>
                            <p>
                                before
                                <a href="https://host.tld/path/">link</a>
                                 after
                            </p>
                            
                            <p>last</p>
                        ',
                    ],
                    [
                        'br_title' => 'link with page in second paragraph',
                        'br_text' => '
                            <p>first</p>
                            <p>
                                before
                                <a href="https://host.tld/path/page.html">link</a>
                                 after
                            </p>
                            
                            <p>last</p>
                        ',
                        'br_text_absolute' => '
                            <p>first</p>
                            <p>
                                before
                                <a href="https://host.tld/path/page.html">link</a>
                                 after
                            </p>
                            
                            <p>last</p>
                        ',
                    ],
                    [
                        'br_title' => 'link with slash and query',
                        'br_text' => '<p>before <a href="https://host.tld/path/?a=1&b[]=2&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/?a=1&b[]=2&c">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link with page and query',
                        'br_text' => '<p>before <a href="https://host.tld/path/page.html?a=1&b[]=2&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/page.html?a=1&b[]=2&c">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link with page and encoded query',
                        'br_text' => '<p>before <a href="https://host.tld/path/page.html?a=1&geo%5Bt%5D%5B14%5D=14&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/page.html?a=1&geo%5Bt%5D%5B14%5D=14&c">link</a> after</p>',
                    ],
                    [
                        'br_title' => 'link with page and encoded cyrillic query',
                        'br_text' => '<p>before <a href="https://host.tld/path/page.html?a=1&key=%D1%81%D0%BB%D0%BE%D0%B2%D0%BE&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/page.html?a=1&key=%D1%81%D0%BB%D0%BE%D0%B2%D0%BE&c">link</a> after</p>',
                    ],
                ],
            ],
        ];
    }
}