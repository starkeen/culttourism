<?php

declare(strict_types=1);

namespace tests\core\application;

use app\core\application\WebApplication;
use app\core\GlobalConfig;
use app\core\page\Content;
use app\core\page\Headers;
use app\core\SessionStorage;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\sys\TemplateEngine;
use MSysProperties;
use PHPUnit\Framework\TestCase;

class WebApplicationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('SENTRY_DSN')) {
            define('SENTRY_DSN', 'https://key1@key2.ingest.sentry.io/12345');
        }
        if (!defined('GLOBAL_ERROR_REPORTING')) {
            define('GLOBAL_ERROR_REPORTING', false);
        }
        if (!defined('GLOBAL_SITE_URL')) {
            define('GLOBAL_SITE_URL', 'site.url');
        }
        $_SERVER['REQUEST_URI'] = '/';
    }

    public function testNotSSLRedirect(): void
    {
        $mockDb = $this->getMockBuilder(MyDB::class)->disableOriginalConstructor()->getMock();
        $mockTemplateEngine = $this->getMockBuilder(TemplateEngine::class)->disableOriginalConstructor()->getMock();
        $mockSysProperties = $this->getMockBuilder(MSysProperties::class)->disableOriginalConstructor()->getMock();
        $mockSession = $this->getMockBuilder(SessionStorage::class)->disableOriginalConstructor()->getMock();
        $mockUser = $this->getMockBuilder(WebUser::class)->disableOriginalConstructor()->getMock();

        $mockRequest = $this->getMockBuilder(SiteRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isSSL', 'getCurrentURL'])
            ->getMock();
        $mockResponse = $this->getMockBuilder(SiteResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHeaders', 'getContent'])
            ->getMock();
        $mockHeaders = $this->getMockBuilder(Headers::class)
            ->onlyMethods(['sendRedirect', 'flush'])
            ->getMock();
        $mockContent = $this->getMockBuilder(Content::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHead'])
            ->getMock();
        $mockGlobalConfig = $this->getMockBuilder(GlobalConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $application = new WebApplication();
        $application->setDb($mockDb);
        $application->setTemplateEngine($mockTemplateEngine);
        $application->setSysPropertiesModel($mockSysProperties);
        $application->setSessionStorage($mockSession);
        $application->setSiteRequest($mockRequest);
        $application->setSiteResponse($mockResponse);
        $application->setWebUser($mockUser);
        $application->setGlobalConfig($mockGlobalConfig);

        $mockRequest->expects(self::once())->method('isSSL')->willReturn(false);
        $mockRequest->expects(self::once())->method('getCurrentURL')->willReturn('current_url');
        $mockResponse->expects(self::exactly(6))->method('getHeaders')->willReturn($mockHeaders);
        $mockResponse->expects(self::exactly(11))->method('getContent')->willReturn($mockContent);
        $mockHeaders->expects(self::once())->method('sendRedirect')->with('current_url');
        $mockHeaders->expects(self::once())->method('flush');

        $application->run();
    }
}
