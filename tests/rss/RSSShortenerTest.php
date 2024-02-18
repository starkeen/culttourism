<?php

namespace tests\rss;

use app\rss\RSSGeneratorInterface;
use app\rss\RSSUrlShortener;
use app\services\shortio\ShortIoClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RSSShortenerTest extends TestCase
{
    /** @var RSSGeneratorInterface */
    private $generator;

    /** @var ShortIoClient|MockObject */
    private $shortener;

    public function setUp(): void
    {
        $this->generator = $this->getMockBuilder(RSSGeneratorInterface::class)
                                ->getMock();
        $this->generator->method('process')
                        ->willReturnCallback(
                            function (array $arg) {
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

        $this->shortener = $this->getMockBuilder(ShortIoClient::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['short'])
                            ->getMock();
        $this->shortener->method('short')->willReturnCallback(
            static function ($arg) {
                return sprintf('https://%s.tld/', md5($arg));
            }
        );
    }

    /**
     * @param array  $in
     * @param string $expected
     * @param int    $count
     *
     * @dataProvider getExamples
     */
    public function testProcessing(array $in, string $expected, int $count): void
    {
        $this->shortener->expects($this->exactly($count))->method('short');

        $urlShortener = new RSSUrlShortener($this->generator, $this->shortener);
        $urlShortener->rootUrl = 'https://host.tld/';
        $out = $urlShortener->process($in);

        $this->assertEquals($expected, $out);
    }

    public static function getExamples(): array
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
                'link without slash' . PHP_EOL
                . '<p>before <a href="https://host.tld">link</a> after</p>' . PHP_EOL
                . '<p>before <a href="https://host.tld" target="_blank">link</a> after</p>',
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
                'link with slash' . PHP_EOL
                . '<p>before <a href="https://host.tld/">link</a> after</p>' . PHP_EOL
                . '<p>before <a href="https://6b94df3c128d0d2be90ae7e67a91bd9e.tld/" target="_blank">link</a> after</p>',
                1,
            ],
        ];
    }
}
