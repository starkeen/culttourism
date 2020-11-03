<?php

declare(strict_types=1);

namespace app\db;

use Exception;
use Throwable;

class MyPDOException extends Exception
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = '[' . $code . '] ' . $message;
        parent::__construct($message, $code, $previous);
    }
}
