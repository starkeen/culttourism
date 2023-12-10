<?php

declare(strict_types=1);

namespace tests\modules;

use app\modules\SignModule;

class SignModuleTest extends AbstractModulesTestingDependencies
{
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

        $module = new SignModule($db, $templateEngine, $webUser, $globalConfig);

        $request = $this->getMockRequest();
        $request->expects(self::once())->method('getModuleKey')->willReturn($key);
        $result = $module->isApplicable($request);
        self::assertEquals($isApplicable, $result);
    }

    /**
     * @return array[]
     */
    public static function getRequestExamples(): array
    {
        return [
            'подходящий запрос' => ['sign', true],
            'неподходящий запрос' => ['other', false],
        ];
    }
}
