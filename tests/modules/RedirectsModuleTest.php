<?php

declare(strict_types=1);

namespace tests\modules;

use app\exceptions\RedirectException;
use app\modules\RedirectsModule;
use MRedirects;
use PHPUnit\Framework\MockObject\MockObject;

class RedirectsModuleTest extends AbstractModulesTestingDependencies
{
    /**
     * @param string $route
     * @param bool $expected
     * @dataProvider getExampleApplicableRoutes
     */
    public function testApplicableMethod(string $route, bool $expected): void
    {
        $db = $this->getMockDb();
        /** @var MRedirects|MockObject $redirectsModel */
        $redirectsModel = $this->getMockModel(MRedirects::class);

        $module = new RedirectsModule($db);
        $module->setRedirectsModel($redirectsModel);

        $redirectsModel->expects(self::once())
            ->method('getActive')
            ->willReturn($this->getRedirectsMap());

        $request = $this->getMockRequest();
        $request->expects(self::once())->method('getUrl')->willReturn($route);
        $request->expects(self::never())->method('getModuleKey');

        $actual = $module->isApplicable($request);
        self::assertEquals($expected, $actual);

        if ($expected) {
            $response = $this->getMockResponse();
            try {
                $module->handle($request, $response);
            } catch (RedirectException $redirectException) {
                self::assertEquals('new_page.html', $redirectException->getTargetUrl());
            }
        }
    }

    /**
     * @return array[]
     */
    public function getExampleApplicableRoutes(): array
    {
        return [
            'не редиректим' => ['one/two/three', false],
            'редиректим' => ['redirect_need', true],
        ];
    }

    private function getRedirectsMap(): array
    {
        return [
            ['rd_from' => '/no/i', 'rd_to' => 'none'],
            ['rd_from' => '/redirect_need/i', 'rd_to' => 'new_page.html'],
        ];
    }
}
