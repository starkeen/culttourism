<?php

declare(strict_types=1);

namespace tests\api\yandex_search;

use app\api\yandex_search\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testResultBuilderSuccess(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?><yandexsearch version="1.0"><response date="20200630T174056"><reqid>abcde</reqid></response></yandexsearch>';
        $result = new Result($xml);

        $this->assertFalse($result->isError());
        $this->assertEquals('abcde', $result->getRequestId());
        $this->assertIsArray($result->getItems());
        $this->assertCount(0, $result->getItems());
        $this->assertEquals(0, $result->getPagesCount());
    }

    public function testResultBuilderError(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?><yandexsearch version="1.0"><response date="20200630T174056"><error code="33">error-text</error><reqid>abcde</reqid></response></yandexsearch>';
        $result = new Result($xml);

        $this->assertTrue($result->isError());
        $this->assertEquals(33, $result->getErrorCode());
        $this->assertEquals('error-text', $result->getErrorText());
        $this->assertEquals('abcde', $result->getRequestId());
    }
}
