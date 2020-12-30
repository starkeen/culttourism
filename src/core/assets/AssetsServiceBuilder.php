<?php

declare(strict_types=1);

namespace app\core\assets;

use config\StaticFilesConfig;
use StaticResources;

class AssetsServiceBuilder
{
    /**
     * @var StaticResources
     */
    private static $instance;

    public static function build(): StaticResources
    {
        if (self::$instance === null) {
            $config = new StaticFilesConfig();
            self::$instance = new StaticResources($config);
        }

        return self::$instance;
    }
}
