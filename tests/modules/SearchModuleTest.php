<?php

declare(strict_types=1);

namespace tests\modules;

use app\modules\SearchModule;
use app\services\YandexSearch\YandexSearchService;

class SearchModuleTest extends AbstractModulesTestingDependencies
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
        $logger = $this->getMockLogger();
        $searchServiceMock = $this->getMockBuilder(YandexSearchService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $module = new SearchModule($db, $templateEngine, $webUser, $globalConfig, $logger, $searchServiceMock);

        $request = $this->getMockRequest();
        $request->expects(self::once())->method('getModuleKey')->willReturn($key);
        $result = $module->isApplicable($request);
        self::assertEquals($isApplicable, $result);
    }

    public static function getRequestExamples(): array
    {
        return [
            'подходящий запрос' => ['search', true],
            'неподходящий запрос' => ['other', false],
        ];
    }
}
