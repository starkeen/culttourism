<?php

declare(strict_types=1);

namespace tests\modules;

use app\core\WebUser;
use app\modules\PictureModule;
use PHPUnit\Framework\MockObject\MockObject;

class PictureModuleTest extends AbstractModulesTestingDependencies
{
    /**
     * @var WebUser|MockObject
     */
    private MockObject $webUser;

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
            'подходящий запрос' => ['picture', true],
            'неподходящий запрос' => ['other', false],
        ];
    }

    private function buildModule(): PictureModule
    {
        $db = $this->getMockDb();
        $this->webUser = $this->getMockWebUser();

        return new PictureModule($db, $this->webUser);
    }
}
