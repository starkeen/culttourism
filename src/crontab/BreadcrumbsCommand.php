<?php

declare(strict_types=1);

namespace app\crontab;

use MPageCities;

class BreadcrumbsCommand extends CrontabCommand
{
    private MPageCities $pageCitiesModel;

    public function __construct(MPageCities $model)
    {
        $this->pageCitiesModel = $model;
    }

    public function run(): void
    {
        $list = $this->pageCitiesModel->getListWithoutBreadcrumbs();

        foreach ($list as $cid => $link) {
            $this->pageCitiesModel->updateBreadcrumbs($cid, $link);
        }
    }
}
