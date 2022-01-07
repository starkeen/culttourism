<?php

declare(strict_types=1);

namespace app\crontab;

use MWordstatTrends;

class WordstatTrendsCommand extends AbstractCrontabCommand
{
    private MWordstatTrends $wordstatTrendsModel;

    public function __construct(MWordstatTrends $ws)
    {
        $this->wordstatTrendsModel = $ws;
    }

    public function run(): void
    {
        $this->wordstatTrendsModel->calcToday();
    }
}
