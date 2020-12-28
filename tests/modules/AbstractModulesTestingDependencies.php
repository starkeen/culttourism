<?php

declare(strict_types=1);

namespace tests\modules;

use app\core\GlobalConfig;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\sys\Logger;
use app\sys\TemplateEngine;
use Model;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractModulesTestingDependencies extends TestCase
{
    /**
     * @return MockObject|MyDB
     */
    protected function getMockDb(): MockObject
    {
        return $this->getMockBuilder(MyDB::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|TemplateEngine
     */
    public function getMockTemplateEngine(): MockObject
    {
        return $this->getMockBuilder(TemplateEngine::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|WebUser
     */
    public function getMockWebUser(): MockObject
    {
        return $this->getMockBuilder(WebUser::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|GlobalConfig
     */
    public function getMockGlobalConfig(): MockObject
    {
        return $this->getMockBuilder(GlobalConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|Logger
     */
    public function getMockLogger(): MockObject
    {
        return $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|SiteRequest
     */
    public function getMockRequest(): MockObject
    {
        return $this->getMockBuilder(SiteRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getModuleKey', 'getUrl', 'getLevel1'])
            ->getMock();
    }

    /**
     * @return MockObject|SiteResponse
     */
    public function getMockResponse(): MockObject
    {
        return $this->getMockBuilder(SiteResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $className
     * @return MockObject|Model
     */
    public function getMockModel(string $className): MockObject
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods(['getActive'])
            ->getMock();
    }
}
