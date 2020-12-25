<?php

declare(strict_types=1);

namespace tests\core;

use app\core\page\Content;
use app\core\page\Headers;
use app\core\SiteResponse;
use PHPUnit\Framework\TestCase;

class SiteResponseTest extends TestCase
{
    public function testBaseMethods(): void
    {
        $headers = $this->getMockBuilder(Headers::class)->disableOriginalConstructor()->getMock();
        $content = $this->getMockBuilder(Content::class)->disableOriginalConstructor()->getMock();

        $siteResponse = new SiteResponse($headers, $content);
        self::assertEquals($content, $siteResponse->getContent());
        self::assertEquals($headers, $siteResponse->getHeaders());

        $siteResponse->setLastEditTimestamp(1234567890);
        self::assertEquals(1234567890, $siteResponse->getLastEditTimestamp());
        self::assertEquals('Fri, 13 Feb 2009 23:31:30 GMT', $siteResponse->getLastEditTimeGMT());
        self::assertEquals('Fri, 20 Feb 2009 23:31:30 GMT', $siteResponse->getExpiresTimeGMT());

        $siteResponse->setMaxLastEditTimestamp(123);
        self::assertEquals(1234567890, $siteResponse->getLastEditTimestamp());
        self::assertEquals('Fri, 13 Feb 2009 23:31:30 GMT', $siteResponse->getLastEditTimeGMT());
        self::assertEquals('Fri, 20 Feb 2009 23:31:30 GMT', $siteResponse->getExpiresTimeGMT());

        $siteResponse->setMaxLastEditTimestamp(1234567890 + 1);
        self::assertEquals(1234567891, $siteResponse->getLastEditTimestamp());
        self::assertEquals('Fri, 13 Feb 2009 23:31:31 GMT', $siteResponse->getLastEditTimeGMT());
        self::assertEquals('Fri, 20 Feb 2009 23:31:31 GMT', $siteResponse->getExpiresTimeGMT());

        $siteResponse->setLastEditTimestampToFuture();
        self::assertEqualsWithDelta(time() + 604800, $siteResponse->getLastEditTimestamp(), 10);
    }
}
