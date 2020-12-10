<?php

declare(strict_types=1);

namespace tests\core;

use app\core\SiteRequest;
use PHPUnit\Framework\TestCase;

class SiteRequestTest extends TestCase
{
    /**
     * @dataProvider getExamples
     *
     * @param string $requestUri
     * @param string $url
     */
    public function testParseAndReconstruct(string $requestUri, string $url): void
    {
        $request = new SiteRequest($requestUri);

        self::assertEquals($url, $request->getUrl());
    }

    public function getExamples(): array
    {
        return [
            'главная страница' => [
                '/',
                '/',
            ],
            'корень модуля' => [
                '/module',
                '/module',
            ],
            'корень модуля с закрытием' => [
                '/module/',
                '/module/',
            ],
            'русскоязычный модуль' => [
                '/модуль',
                '/модуль',
            ],
            'страница модуля' => [
                '/module/page.html',
                '/module/page.html',
            ],
            'страница модуля с параметрами' => [
                '/module/page.html?param=value',
                '/module/page.html',
            ],
            'раздел модуля с закрытием' => [
                '/module/sub/',
                '/module/sub/',
            ],
            'раздел модуля без закрытия' => [
                '/module/sub',
                '/module/sub',
            ],
            'раздел модуля с параметрами' => [
                '/module/sub/?param=value',
                '/module/sub/',
            ],
            'страница внутри раздела модуля с параметрами' => [
                '/module/sub/page.html?param=value',
                '/module/sub/page.html',
            ],
        ];
    }
}
