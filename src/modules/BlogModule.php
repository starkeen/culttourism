<?php

declare(strict_types=1);

namespace app\modules;

use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\db\MyDB;

class BlogModule implements ModuleInterface
{
    /**
     * @var MyDB
     */
    private $db;

    public function __construct(MyDB $db)
    {
        $this->db = $db;
    }

    public function process(SiteRequest $request, SiteResponse $response): void
    {}

    public function isApplicable(SiteRequest $request): bool
    {
        return true;
    }
}
