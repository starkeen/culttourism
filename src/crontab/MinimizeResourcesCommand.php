<?php

declare(strict_types=1);

namespace app\crontab;

use app\core\assets\AssetsServiceBuilder;
use MSysProperties;

class MinimizeResourcesCommand extends AbstractCrontabCommand
{
    private MSysProperties $systemPropertiesModel;

    public function __construct(MSysProperties $sp)
    {
        $this->systemPropertiesModel = $sp;
    }

    public function run(): void
    {
        $static = AssetsServiceBuilder::build()->rebuildAll();

        if (isset($static['css']['common'])) {
            $this->systemPropertiesModel->updateByName('mainfile_css', basename($static['css']['common']));
        }
        if (isset($static['js']['common'])) {
            $this->systemPropertiesModel->updateByName('mainfile_js', basename($static['js']['common']));
        }

        foreach ($static as $type => $packs) {
            foreach ($packs as $pack => $file) {
                $this->systemPropertiesModel->updateByName('res_' . $type . '_' . $pack, basename($file));
            }
        }
    }
}
