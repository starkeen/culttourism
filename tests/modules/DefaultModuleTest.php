<?php

declare(strict_types=1);

namespace tests\modules;

use app\modules\DefaultModule;

class DefaultModuleTest extends AbstractModulesTestingDependencies
{
    /**
     */
    public function testApplicableMethod(): void
    {
        $db = $this->getMockDb();
        $templateEngine = $this->getMockTemplateEngine();
        $webUser = $this->getMockWebUser();
        $globalConfig = $this->getMockGlobalConfig();

        $module = new DefaultModule($db, $templateEngine, $webUser, $globalConfig);

        $request = $this->getMockRequest();
        $request->expects(self::never())->method('getModuleKey');
        $result = $module->isApplicable($request);
        self::assertTrue($result);
    }
}
