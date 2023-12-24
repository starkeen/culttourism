<?php

declare(strict_types=1);

namespace app\core\module;

use app\core\SiteRequest;
use app\core\SiteResponse;

interface ModuleInterface
{
    /**
     * Проверяет применимость модуля к текущему запросу
     *
     * @param  SiteRequest $request
     * @return bool
     */
    public function isApplicable(SiteRequest $request): bool;

    /**
     * Обработка запроса
     *
     * @param SiteRequest  $request
     * @param SiteResponse $response
     */
    public function handle(SiteRequest $request, SiteResponse $response): void;
}
