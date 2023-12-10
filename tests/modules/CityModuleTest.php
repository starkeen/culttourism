<?php

declare(strict_types=1);

namespace tests\modules;

use app\exceptions\NotFoundException;
use app\modules\CityModule;

class CityModuleTest extends AbstractModulesTestingDependencies
{
    public function testUndefinedRoute(): void
    {
        $module = $this->buildModule();

        $request = $this->getMockRequest();
        $response = $this->getMockResponse();
        $request->expects(self::exactly(5))->method('getLevel1')->willReturn('undefined_route');

        $this->expectException(NotFoundException::class);

        $module->handle($request, $response);
    }

    public function testWeatherRouteWithoutLatLon(): void
    {
        $module = $this->buildModule();

        $request = $this->getMockRequest();
        $response = $this->getMockResponse();
        $request->expects(self::exactly(5))->method('getLevel1')->willReturn('weather');

        $this->expectException(NotFoundException::class);

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

    public static function getRequestExamples(): array
    {
        return [
            'подходящий запрос' => ['city', true],
            'неподходящий запрос' => ['other', false],
        ];
    }

    /**
     * @return CityModule
     */
    private function buildModule(): CityModule
    {
        $db = $this->getMockDb();
        $templateEngine = $this->getMockTemplateEngine();
        $webUser = $this->getMockWebUser();
        $globalConfig = $this->getMockGlobalConfig();

        return new CityModule($db, $templateEngine, $webUser, $globalConfig);
    }
}
