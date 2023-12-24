<?php

declare(strict_types=1);

namespace app\modules;

use app\core\assets\AssetsServiceBuilder;
use app\core\assets\constant\Pack;
use app\core\assets\constant\Type;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;

class SysModule implements ModuleInterface
{
    /**
     * @inheritDoc
     * @throws     NotFoundException
     * @throws     RedirectException
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        if ($request->getLevel2() !== null) {
            throw new NotFoundException();
        }

        if ($request->getLevel1() === null && empty($request->getGET())) {
            throw new RedirectException('/');
        }

        if (
            $request->getLevel1() === 'static'
            && $request->getGETParam('type') !== null
            && $request->getGETParam('pack') !== null
        ) {
            $typeName = trim($request->getGETParam('type'));
            $packName = trim($request->getGETParam('pack'));
            $type = new Type($typeName);
            $pack = new Pack($packName);
            $this->getStatic($type, $pack);
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'sys';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === 'sys';
    }

    /**
     * @param Type $type
     * @param Pack $pack
     */
    private function getStatic(Type $type, Pack $pack): void
    {
        header('Content-Type: ' . $type->getContentType());

        echo AssetsServiceBuilder::build()->getFull($type, $pack);
        exit();
    }
}
