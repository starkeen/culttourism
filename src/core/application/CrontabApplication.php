<?php

declare(strict_types=1);

namespace app\core\application;

class CrontabApplication extends Application
{
    public function init(): void
    {
        parent::init();
    }

    public function run(): void
    {
        $this->init();
    }
}
