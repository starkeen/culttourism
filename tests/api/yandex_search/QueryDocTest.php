<?php

declare(strict_types=1);

namespace tests\api\yandex_search;

use app\api\yandex_search\QueryDoc;
use PHPUnit\Framework\TestCase;

class QueryDocTest extends TestCase
{
    public function testBuilder(): void
    {
        $doc = new QueryDoc();
        $doc->setKeywords('keywords');
        $doc->setPage(123);
        $doc->setMaxDocumentsPerPage(321);

        $expected = '<?xml version="1.0" encoding="utf-8"?>
<request><query>keywords</query><page>123</page><sortby order="descending" priority="no">rlv</sortby><maxpassages>5</maxpassages><groupings><groupby attr="" mode="flat" groups-on-page="321" docs-in-group="1" curcateg="-1"/></groupings></request>' . PHP_EOL;
        $this->assertEquals($expected, $doc->getBody());
        $this->assertEquals(285, $doc->getLength());
    }
}
