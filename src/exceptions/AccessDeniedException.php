<?php

declare(strict_types=1);

namespace app\exceptions;

class AccessDeniedException extends LogicException
{
    public function __construct()
    {
        parent::__construct('access denied', 403);
    }
}
