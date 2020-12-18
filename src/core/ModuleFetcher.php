<?php

declare(strict_types=1);

namespace app\core;

use app\db\MyDB;
use Page;

class ModuleFetcher
{
    /**
     * @var MyDB
     */
    private $db;

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->db = $db;
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
}
