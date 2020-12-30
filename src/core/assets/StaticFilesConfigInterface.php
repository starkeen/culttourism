<?php

declare(strict_types=1);

namespace app\core\assets;

interface StaticFilesConfigInterface
{
    /**
     * @return array
     */
    public function getCSSList(): array;

    /**
     * @return array
     */
    public function getJavascriptList(): array;
}
