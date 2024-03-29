<?php

namespace tests\rss;

use app\rss\RSSGeneratorInterface;
use app\rss\RSSAddUTM;
use PHPUnit\Framework\TestCase;

class RSSAddUTMTest extends TestCase
{
    /** @var RSSGeneratorInterface */
    private $generator;

    public function setUp(): void
    {
        $this->generator = $this->getMockBuilder(RSSGeneratorInterface::class)->getMock();
        $this->generator->method('process')
                        ->willReturnCallback(
                            function ($arg) {
                                $out = [];
                                foreach ($arg as $item) {
                                    $out[] = implode(
                                        PHP_EOL,
                                        [
                                            'br_title' => $item['br_title'],
                                            'br_text' => $item['br_text'],
                                            'br_text_absolute' => $item['br_text_absolute'],
                                        ]
                                    );
                                }
                                return implode(PHP_EOL, $out);
                            }
                        );
    }

    /**
     * @param array  $in
     * @param string $expected
     *
     * @dataProvider getExamples
     */
    public function testProcessing(array $in, string $expected)
    {
        $component = new RSSAddUTM($this->generator, 'phpunit');
        $component->rootUrl = 'https://host.tld';

        $out = $component->process($in);

        $this->assertEquals($expected, $out);
    }

    public static function getExamples(): array
    {
        return [
            [
                [
                    [
                        'br_date' => '2017-04-29 10:25:48',
                        'br_title' => 'link without slash',
                        'br_text' => '<p>before <a href="https://host.tld">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld" target="_blank">link</a> after</p>',
                    ],
                    [
                        'br_date' => '2017-04-29 10:25:48',
                        'br_title' => 'link with slash',
                        'br_text' => '<p>before <a href="https://host.tld/path/">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/" target="_blank">link</a> after</p>',
                    ],
                ],
                'link without slash' . PHP_EOL
                . '<p>before <a href="https://host.tld">link</a> after</p>' . PHP_EOL
                . '<p>before <a href="https://host.tld/?utm_source=phpunit&utm_medium=blog&utm_content=20170429&utm_campaign=feed" target="_blank">link</a> after</p>' . PHP_EOL
                . 'link with slash' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/">link</a> after</p>' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/?utm_source=phpunit&utm_medium=blog&utm_content=20170429&utm_campaign=feed" target="_blank">link</a> after</p>',
            ],
            [
                [
                    [
                        'br_date' => '2017-04-29 10:25:48',
                        'br_title' => 'link in second paragraph',
                        'br_text' => '<p>first></p><p>before <a href="https://host.tld">link</a> after</p><p>last</p>',
                        'br_text_absolute' => '<p>first></p><p>before <a href="https://host.tld" target="_blank">link</a> after</p><p>last</p>',
                    ],
                    [
                        'br_date' => '2017-04-29 10:25:48',
                        'br_title' => 'link with page in second paragraph',
                        'br_text' => '<p>first></p><p>before <a href="https://host.tld/path/">link</a> after</p><p>last</p>',
                        'br_text_absolute' => '<p>first></p><p>before <a href="https://host.tld/path/" target="_blank">link</a> after</p><p>last</p>',
                    ],
                ],
                'link in second paragraph' . PHP_EOL
                . '<p>first></p><p>before <a href="https://host.tld">link</a> after</p><p>last</p>' . PHP_EOL
                . '<p>first></p><p>before <a href="https://host.tld/?utm_source=phpunit&utm_medium=blog&utm_content=20170429&utm_campaign=feed" target="_blank">link</a> after</p><p>last</p>' . PHP_EOL
                . 'link with page in second paragraph' . PHP_EOL
                . '<p>first></p><p>before <a href="https://host.tld/path/">link</a> after</p><p>last</p>' . PHP_EOL
                . '<p>first></p><p>before <a href="https://host.tld/path/?utm_source=phpunit&utm_medium=blog&utm_content=20170429&utm_campaign=feed" target="_blank">link</a> after</p><p>last</p>',
            ],
            [
                [
                    [
                        'br_date' => '2017-04-29 10:25:48',
                        'br_title' => 'link with slash and query',
                        'br_text' => '<p>before <a href="https://host.tld/path/?a=1&b[]=2&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/?a=1&b[]=2&c">link</a> after</p>',
                    ],
                    [
                        'br_date' => '2017-04-29 10:25:48',
                        'br_title' => 'link with page and query',
                        'br_text' => '<p>before <a href="https://host.tld/path/page.html?a=1&b[]=2&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/page.html?a=1&b[]=2&c">link</a> after</p>',
                    ],
                ],
                'link with slash and query' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/?a=1&b[]=2&c">link</a> after</p>' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/?a=1&b[0]=2&c=&utm_source=phpunit&utm_medium=blog&utm_content=20170429&utm_campaign=feed">link</a> after</p>' . PHP_EOL
                . 'link with page and query' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/page.html?a=1&b[]=2&c">link</a> after</p>' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/page.html?a=1&b[0]=2&c=&utm_source=phpunit&utm_medium=blog&utm_content=20170429&utm_campaign=feed">link</a> after</p>',
            ],
            [
                [
                    [
                        'br_date' => '2017-04-29 10:25:48',
                        'br_title' => 'link with page and encoded query',
                        'br_text' => '<p>before <a href="https://host.tld/path/page.html?a=1&geo%5Bt%5D%5B14%5D=14&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/page.html?a=1&geo%5Bt%5D%5B14%5D=14&c">link</a> after</p>',
                    ],
                    [
                        'br_date' => '2017-04-29 10:25:48',
                        'br_title' => 'link with page and encoded cyrillic query',
                        'br_text' => '<p>before <a href="https://host.tld/path/page.html?a=1&key=%D1%81%D0%BB%D0%BE%D0%B2%D0%BE&c">link</a> after</p>',
                        'br_text_absolute' => '<p>before <a href="https://host.tld/path/page.html?a=1&key=%D1%81%D0%BB%D0%BE%D0%B2%D0%BE&c">link</a> after</p>',
                    ],
                ],
                'link with page and encoded query' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/page.html?a=1&geo%5Bt%5D%5B14%5D=14&c">link</a> after</p>' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/page.html?a=1&geo[t][14]=14&c=&utm_source=phpunit&utm_medium=blog&utm_content=20170429&utm_campaign=feed">link</a> after</p>' . PHP_EOL
                . 'link with page and encoded cyrillic query' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/page.html?a=1&key=%D1%81%D0%BB%D0%BE%D0%B2%D0%BE&c">link</a> after</p>' . PHP_EOL
                . '<p>before <a href="https://host.tld/path/page.html?a=1&key=слово&c=&utm_source=phpunit&utm_medium=blog&utm_content=20170429&utm_campaign=feed">link</a> after</p>',
            ],
        ];
    }
}
