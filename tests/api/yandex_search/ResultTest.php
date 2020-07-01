<?php

declare(strict_types=1);

namespace tests\api\yandex_search;

use app\api\yandex_search\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testResultBuilderSuccess(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
    <yandexsearch version="1.0">
        <request>
            <groupings>
                <groupby groups-on-page="987">
                </groupby>
            </groupings>
        </request>
        <response date="20200630T174056">
            <reqid>abcde</reqid>
            <results>
                <grouping groups-on-page="123">
                    <found-docs>321</found-docs>
                    <group>
                        <doc id="Z5C936E121190B07B">
                            <url>https://host1/path</url>
                            <title>title with <hlword>highlight</hlword> word</title>
                            <modtime>20140520T183120</modtime>
                            <headline>headline not <hlword>for</hlword> usage</headline>
                            <passages>
                                <passage>passage first</passage>
                                <passage>passage with <hlword>highlighted</hlword> word</passage>
                                <passage>passage last</passage>
                            </passages>
                        </doc>
                    </group>
                    <group>
                        <doc id="Z5C936E121190B07B">
                            <url>https://host2/path</url>
                            <title>title with headline</title>
                            <headline>headline with <hlword>some highlighted</hlword> word</headline>
                            <modtime>20140520T183120</modtime>
                        </doc>
                    </group>
                    <group>
                        <doc id="Z5C936E121190B07B">
                            <url>https://host3/path</url>
                            <title>title without passages</title>
                            <modtime>20140520T183120</modtime>
                        </doc>
                    </group>
                </grouping>
            </results>
        </response>
    </yandexsearch>';
        $result = new Result($xml);

        $this->assertFalse($result->isError());
        $this->assertEquals('abcde', $result->getRequestId());
        $this->assertIsArray($result->getItems());
        $this->assertCount(3, $result->getItems());
        $this->assertEquals(3, $result->getPagesCount());

        $resultItem1 = $result->getItems()[0];
        $this->assertEquals('https://host1/path', $resultItem1->getUrl());
        $this->assertEquals('title with <strong>highlight</strong> word', $resultItem1->getTitle());
        $this->assertEquals('passage first&hellip; passage with highlighted word&hellip; passage last', $resultItem1->getDescription());
        $resultItem2 = $result->getItems()[1];
        $this->assertEquals('https://host2/path', $resultItem2->getUrl());
        $this->assertEquals('title with headline', $resultItem2->getTitle());
        $this->assertEquals('headline with some highlighted word', $resultItem2->getDescription());
        $resultItem3 = $result->getItems()[2];
        $this->assertEquals('https://host3/path', $resultItem3->getUrl());
        $this->assertEquals('title without passages', $resultItem3->getTitle());
        $this->assertEquals('', $resultItem3->getDescription());
    }

    public function testResultBuilderError(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <yandexsearch version="1.0">
                    <response date="20200630T174056">
                        <error code="33">error-text</error>
                        <reqid>abcde</reqid>
                    </response>
                </yandexsearch>';
        $result = new Result($xml);

        $this->assertTrue($result->isError());
        $this->assertEquals(33, $result->getErrorCode());
        $this->assertEquals('error-text', $result->getErrorText());
        $this->assertEquals('abcde', $result->getRequestId());
    }
}
