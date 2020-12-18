<?php

declare(strict_types=1);

namespace app\core;

use Throwable;

class ExceptionsHandler
{
    /**
     * @param Throwable $e
     */
    public static function errorsExceptionsHandler(Throwable $e): void
    {
        $msg = 'Error: ' . $e->getMessage() . PHP_EOL
            . 'file: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL
            . 'URI: ' . ($_SERVER['REQUEST_URI'] ?? 'undefined') . PHP_EOL .  PHP_EOL
            .  '__________________________' . PHP_EOL . PHP_EOL . PHP_EOL
            . 'trace: ' . print_r($e->getTrace(), true) . PHP_EOL;

        mail('starkeen@gmail.com', 'Error on ' . _URL_ROOT, $msg);
        if (ob_get_length()) {
            ob_end_clean();
        }
    }
}
