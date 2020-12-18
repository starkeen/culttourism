<?php

declare(strict_types=1);

namespace app\core\module;

use app\core\SiteRequest;
use app\db\MyDB;
use Page;

class ModuleFetcher
{
    /**
     * @var MyDB
     */
    private $db;

    /**
     * @var ModuleInterface[]
     */
    private $modules;

    /**
     * @param MyDB $db
     * @param ModuleInterface[] $modules
     */
    public function __construct(MyDB $db, array $modules)
    {
        $this->db = $db;
        $this->modules = $modules;
    }

    /**
     * @param SiteRequest $request
     * @return Page
     */
    public function getPageModule(SiteRequest $request): Page
    {
        $moduleKey = $request->getModuleKey();

        $includeModulePath = _DIR_INCLUDES . '/class.Page.php';
        $customModulePath = sprintf('%s/%s/%s.php', _DIR_MODULES, $moduleKey, $moduleKey);
        if (file_exists($customModulePath)) {
            $includeModulePath = $customModulePath;
        }
        include($includeModulePath);

        return new Page($this->db, $request);
    }

    /**
     * @param SiteRequest $request
     * @return ModuleInterface
     */
    public function getModule(SiteRequest $request): ModuleInterface
    {
        foreach ($this->modules as $module) {
            if ($module->isApplicable($request)) {
                return $module;
            }
        }
    }
}
