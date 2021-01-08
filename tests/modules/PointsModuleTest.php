<?php

declare(strict_types=1);

namespace tests\modules;

use app\exceptions\AccessDeniedException;
use app\exceptions\NotFoundException;
use app\modules\PointsModule;

class PointsModuleTest extends AbstractModulesTestingDependencies
{
    private $webUser;

    public function testNoAjaxRequest(): void
    {
        $module = $this->buildModule();

        $request = $this->getMockRequest();
        $response = $this->getMockResponse();
        $request->expects(self::once())->method('isAjax')->willReturn(false);

        $this->expectException(NotFoundException::class);

        $module->handle($request, $response);
    }

    public function testNoPostRequest(): void
    {
        $module = $this->buildModule();

        $request = $this->getMockRequest();
        $response = $this->getMockResponse();

        $request->expects(self::once())->method('isAjax')->willReturn(true);
        $request->expects(self::once())->method('isPost')->willReturn(false);

        $this->expectException(NotFoundException::class);

        $module->handle($request, $response);
    }

    public function testPostRequestWithoutId(): void
    {
        $module = $this->buildModule();

        $request = $this->getMockRequest();
        $response = $this->getMockResponse();

        $request->expects(self::once())->method('isAjax')->willReturn(true);
        $request->expects(self::once())->method('isPost')->willReturn(true);
        $request->expects(self::once())->method('getLevel1')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $module->handle($request, $response);
    }

    public function testPostRequestWithNullId(): void
    {
        $module = $this->buildModule();

        $request = $this->getMockRequest();
        $response = $this->getMockResponse();

        $request->expects(self::once())->method('isAjax')->willReturn(true);
        $request->expects(self::once())->method('isPost')->willReturn(true);
        $request->expects(self::exactly(2))->method('getLevel1')->willReturn('0');

        $this->expectException(NotFoundException::class);

        $module->handle($request, $response);
    }

    public function testPostRequestWithIdAndUndefinedMethod(): void
    {
        $module = $this->buildModule();

        $request = $this->getMockRequest();
        $response = $this->getMockResponse();

        $request->expects(self::once())->method('isAjax')->willReturn(true);
        $request->expects(self::once())->method('isPost')->willReturn(true);
        $request->expects(self::exactly(2))->method('getLevel1')->willReturn('123');
        $request->expects(self::once())->method('getLevel2')->willReturn('undefined');

        $this->expectException(NotFoundException::class);

        $module->handle($request, $response);
    }

    public function testPostRequestWithIdAndNotAdminMethod(): void
    {
        $module = $this->buildModule();

        $request = $this->getMockRequest();
        $response = $this->getMockResponse();

        $request->expects(self::once())->method('isAjax')->willReturn(true);
        $request->expects(self::once())->method('isPost')->willReturn(true);
        $request->expects(self::exactly(2))->method('getLevel1')->willReturn('123');
        $request->expects(self::once())->method('getLevel2')->willReturn('contacts');
        $this->webUser->expects(self::once())->method('isEditor')->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $module->handle($request, $response);
    }

    /**
     * @param string $key
     * @param bool $isApplicable
     * @dataProvider getRequestExamples
     */
    public function testApplicableMethod(string $key, bool $isApplicable): void
    {
        $module = $this->buildModule();

        $request = $this->getMockRequest();
        $request->expects(self::once())->method('getModuleKey')->willReturn($key);
        $result = $module->isApplicable($request);
        self::assertEquals($isApplicable, $result);
    }

    /**
     * @return array[]
     */
    public function getRequestExamples(): array
    {
        return [
            'подходящий запрос' => ['point', true],
            'неподходящий запрос' => ['other', false],
        ];
    }

    private function buildModule(): PointsModule
    {
        $db = $this->getMockDb();
        $templateEngine = $this->getMockTemplateEngine();
        $this->webUser = $this->getMockWebUser();
        $globalConfig = $this->getMockGlobalConfig();

        return new PointsModule($db, $templateEngine, $this->webUser, $globalConfig);
    }
}
