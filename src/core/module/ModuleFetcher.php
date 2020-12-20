<?php

declare(strict_types=1);

namespace app\core\module;

use app\core\SiteRequest;

class ModuleFetcher
{
    /**
     * @var ModuleInterface[]
     */
    private $modules;

    /**
     * @param ModuleInterface[] $modules
     */
    public function __construct(array $modules)
    {
        $this->modules = $modules;
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
