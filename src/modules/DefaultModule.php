<?php

declare(strict_types=1);

namespace app\modules;

use app\core\SiteRequest;
use app\core\SiteResponse;
use app\db\MyDB;

class DefaultModule
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
     * @inheritDoc
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {}

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return false;
    }
}
