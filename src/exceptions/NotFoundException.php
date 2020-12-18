<?php

declare(strict_types=1);

namespace app\exceptions;

class NotFoundException extends LogicApplicationException
{
    public function __construct()
    {
        parent::__construct('not found', 404);
    }
}
