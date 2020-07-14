<?php

declare(strict_types=1);

namespace app\api\google_search\exception;

class QuotaExceededException extends SearchException
{
    public function __construct()
    {
        parent::__construct('Достигнута суточная квота', 429);
    }
}
