<?php

declare(strict_types=1);

namespace app\modules;

use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\exceptions\NotFoundException;

class AboutModule extends Module implements ModuleInterface
{
    /**
     * @inheritDoc
     * @throws     NotFoundException
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        if ($request->getLevel1() !== null) {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'about';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }
}
