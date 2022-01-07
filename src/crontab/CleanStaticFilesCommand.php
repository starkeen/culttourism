<?php

declare(strict_types=1);

namespace app\crontab;

use app\core\assets\AssetsServiceBuilder;

class CleanStaticFilesCommand extends AbstractCrontabCommand
{
    public function run(): void
    {
        AssetsServiceBuilder::build()->clean();
    }
}
