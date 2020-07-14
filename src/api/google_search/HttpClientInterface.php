<?php

declare(strict_types=1);

namespace app\api\google_search;

use app\api\google_search\exception\SearchException;

interface HttpClientInterface
{
    /**
     * @param Request $request
     *
     * @return string
     * @throws SearchException
     */
    public function fetchResponse(Request $request): string;
}
