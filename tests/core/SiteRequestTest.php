<?php

declare(strict_types=1);

namespace tests\core;

use app\core\SiteRequest;
use PHPUnit\Framework\TestCase;

class SiteRequestTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        define('_INDEXPAGE_URI', 'ndx');
    }

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
                '/ndx',
            ],
            'корень модуля' => [
                '/module',
                '/module',
            ],
            'корень модуля с закрытием' => [
                '/module/',
                '/module',
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
                '/module/sub',
            ],
            'раздел модуля без закрытия' => [
                '/module/sub',
                '/module/sub',
            ],
            'раздел модуля с параметрами' => [
                '/module/sub/?param=value',
                '/module/sub',
            ],
            'страница внутри раздела модуля с параметрами' => [
                '/module/sub/page.html?param=value',
                '/module/sub/page.html',
            ],
            'подраздел модуля с закрытием' => [
                '/module/sub/subSub/',
                '/module/sub/subSub',
            ],
            'подраздел модуля без закрытия' => [
                '/module/sub/subSub',
                '/module/sub/subSub',
            ],
            'подраздел модуля с параметрами' => [
                '/module/sub/subSub/?param=value',
                '/module/sub/subSub',
            ],
            'страница внутри подраздела модуля с параметрами' => [
                '/module/sub/subSub/page.html?param=value',
                '/module/sub/subSub/page.html',
            ],
            'вложенный подраздел модуля с закрытием' => [
                '/module/sub/subSub/nested/',
                '/module/sub/subSub/nested',
            ],
            'вложенный подраздел модуля без закрытия' => [
                '/module/sub/subSub/nested',
                '/module/sub/subSub/nested',
            ],
            'вложенный подраздел модуля с параметрами' => [
                '/module/sub/subSub/nested/?param=value',
                '/module/sub/subSub/nested',
            ],
            'страница внутри вложенного подраздела модуля с параметрами' => [
                '/module/sub/subSub/nested/page.html?param=value',
                '/module/sub/subSub/nested',
            ],
            'страница внутри кириллического подраздела модуля с параметрами' => [
                '/module/sub/subSub/подРаздел/page.html?param=значение',
                '/module/sub/subSub/подРаздел',
            ],
        ];
    }
}
