<?php

declare(strict_types=1);

namespace app\core\module;

abstract class BaseModule
{
    abstract public function process(): void;
}
