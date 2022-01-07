<?php

declare(strict_types=1);

namespace app\crontab;

use MSearchLog;

class CleanSearchCacheCommand extends AbstractCrontabCommand
{
    /**
     * @var MSearchLog
     */
    private $cacheModel;

    public function __construct(MSearchLog $cacheModel)
    {
        $this->cacheModel = $cacheModel;
    }

    public function run(): void
    {
        $this->cacheModel->deleteOldRecords('-1 month');
    }
}
