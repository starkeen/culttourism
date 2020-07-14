<?php

declare(strict_types=1);

namespace app\crontab;

abstract class CrontabCommand
{
    abstract public function run(): void;
}
