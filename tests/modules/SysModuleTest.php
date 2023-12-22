<?php

declare(strict_types=1);

namespace tests\modules;

use app\modules\SysModule;

class SysModuleTest extends AbstractModulesTestingDependencies
{
    /**
     * @param string $key
     * @param bool $isApplicable
     * @dataProvider getRequestExamples
     */
    public function testApplicableMethod(string $key, bool $isApplicable): void
    {
        $module = new SysModule();

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
            'подходящий запрос' => ['sys', true],
            'неподходящий запрос' => ['other', false],
        ];
    }
}
