<?php

declare(strict_types=1);

namespace tests\core\module;

use app\core\module\ModuleFetcher;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use PHPUnit\Framework\TestCase;

class ModuleFetcherTest extends TestCase
{
    public function testFetcher(): void
    {
        $module1 = $this->getMockBuilder(ModuleInterface::class)
            ->onlyMethods(['isApplicable', 'handle'])
            ->getMock();
        $module2 = $this->getMockBuilder(ModuleInterface::class)
            ->onlyMethods(['isApplicable', 'handle'])
            ->getMock();
        $module3 = $this->getMockBuilder(ModuleInterface::class)
            ->onlyMethods(['isApplicable', 'handle'])
            ->getMock();
        $request = $this->getMockBuilder(SiteRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $modules = [$module1, $module2, $module3];

        $fetcher = new ModuleFetcher($modules);

        $module1->expects(self::once())->method('isApplicable')->with($request)->willReturn(false);
        $module2->expects(self::once())->method('isApplicable')->with($request)->willReturn(true);
        $module3->expects(self::never())->method('isApplicable');

        $resultModule = $fetcher->getModule($request);

        self::assertEquals($module2, $resultModule);
    }
}
