<?php

declare(strict_types=1);

namespace app\modules;

use app\core\GlobalConfig;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\sys\Logger;
use app\sys\TemplateEngine;
use Page;

/**
 * @deprecated
 */
class CoreModule implements ModuleInterface
{
    /**
     * @var MyDB
     */
    private $db;

    /**
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @var TemplateEngine
     */
    private $templateEngine;

    /**
     * @var WebUser
     */
    private $user;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param MyDB           $db
     * @param TemplateEngine $templateEngine
     * @param WebUser        $user
     * @param GlobalConfig   $globalConfig
     * @param Logger         $logger
     */
    public function __construct(MyDB $db, TemplateEngine $templateEngine, WebUser $user, GlobalConfig $globalConfig, Logger $logger)
    {
        $this->db = $db;
        $this->globalConfig = $globalConfig;
        $this->templateEngine = $templateEngine;
        $this->user = $user;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        $page = $this->getPageModule($request);
        $page->response = $response;
        $page->init();
        $page->compileContent();
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return true;
    }

    /**
     * @param SiteRequest $request
     * @return Page
     */
    private function getPageModule(SiteRequest $request): Page
    {
        $moduleKey = $request->getModuleKey();

        $includeModulePath = _DIR_INCLUDES . '/class.Page.php';
        $customModulePath = sprintf('%s/%s/%s.php', _DIR_MODULES, $moduleKey, $moduleKey);
        if (file_exists($customModulePath)) {
            $includeModulePath = $customModulePath;
        }
        include($includeModulePath);

        $page = new Page($this->db, $request);
        $page->globalConfig = $this->globalConfig;
        $page->templateEngine = $this->templateEngine;
        $page->logger = $this->logger;
        $page->webUser = $this->user;

        return $page;
    }
}
