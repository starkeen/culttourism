<?php

declare(strict_types=1);

namespace app\core\module;

use app\core\SiteRequest;
use app\core\SiteResponse;

interface ModuleInterface
{
    /**
     * Обработка запроса
     * @param SiteRequest  $request
     * @param SiteResponse $response
     */
    public function process(SiteRequest $request, SiteResponse $response): void;

    /**
     * Проверяет применимость модуля к текущему запросу
     * @param SiteRequest $request
     * @return bool
     */
    public function isApplicable(SiteRequest $request): bool;
}
