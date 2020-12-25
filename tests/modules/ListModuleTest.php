<?php

declare(strict_types=1);

namespace tests\modules;

use app\modules\FeedbackModule;
use app\modules\ListModule;
use app\modules\SearchModule;

class ListModuleTest extends AbstractModulesTestingDependencies
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

        $module = new ListModule($db, $templateEngine, $webUser, $globalConfig);

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
            'подходящий запрос' => ['list', true],
            'неподходящий запрос' => ['other', false],
        ];
    }
}
