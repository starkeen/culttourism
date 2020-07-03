<?php

declare(strict_types=1);

namespace app\api\google_search;

interface HttpClientInterface
{
    public function fetchResponse(Request $request): string;
}
