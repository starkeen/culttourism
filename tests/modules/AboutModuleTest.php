<?php

declare(strict_types=1);

namespace tests\modules;

use app\exceptions\NotFoundException;
use app\modules\AboutModule;

class AboutModuleTest extends AbstractModulesTestingDependencies
{
    public static function setUpBeforeClass(): void
    {
        $_SERVER['REQUEST_URI'] = 'request_uri';
        if (!defined('GLOBAL_SITE_URL')) {
            define('GLOBAL_SITE_URL', 'site.url');
        }
    }

    public function testProcessUndefinedRoute(): void
    {
        $db = $this->getMockDb();
        $templateEngine = $this->getMockTemplateEngine();
        $webUser = $this->getMockWebUser();
        $globalConfig = $this->getMockGlobalConfig();

        $request = $this->getMockRequest();
        $response = $this->getMockResponse();

        $request->expects(self::once())->method('getLevel1')->willReturn('some_text');

        $module = new AboutModule($db, $templateEngine, $webUser, $globalConfig);

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
        $db = $this->getMockDb();
        $templateEngine = $this->getMockTemplateEngine();
        $webUser = $this->getMockWebUser();
        $globalConfig = $this->getMockGlobalConfig();

        $module = new AboutModule($db, $templateEngine, $webUser, $globalConfig);

        $request = $this->getMockRequest();
        $request->expects(self::once())->method('getModuleKey')->willReturn($key);
        $result = $module->isApplicable($request);
        self::assertEquals($isApplicable, $result);
    }

    public static function getRequestExamples(): array
    {
        return [
            'подходящий запрос' => ['about', true],
            'неподходящий запрос' => ['other', false],
        ];
    }
}
