<?php

declare(strict_types=1);

namespace app\crontab;

use Mailing;

class MailerCommand extends AbstractCrontabCommand
{
    private const PORTION = 10;

    public function run(): void
    {
        Mailing::sendFromPool(self::PORTION);
    }
}
