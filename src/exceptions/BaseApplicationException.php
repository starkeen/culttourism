<?php

declare(strict_types=1);

namespace app\exceptions;

use Exception;
use Throwable;

class BaseApplicationException extends Exception
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('runtime exception', 500, $previous);
    }
}
