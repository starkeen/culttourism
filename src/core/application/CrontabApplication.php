<?php

declare(strict_types=1);

namespace app\core\application;

class CrontabApplication extends Application
{
    public function run(): void
    {
        $this->init();
    }
}
