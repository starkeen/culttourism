<?php

declare(strict_types=1);

namespace app\core;

use app\sys\Logger;
use Throwable;

class ExceptionsHandler
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Throwable $exception
     */
    public function errorsExceptionsHandler(Throwable $exception): void
    {
        $msg = 'Error: ' . $exception->getMessage() . PHP_EOL
            . 'file: ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL
            . 'URI: ' . ($_SERVER['REQUEST_URI'] ?? 'undefined') . PHP_EOL . PHP_EOL
            . '__________________________' . PHP_EOL . PHP_EOL . PHP_EOL
            . 'trace: ' . print_r($exception->getTrace(), true) . PHP_EOL;

        mail('starkeen@gmail.com', 'Error on ' . _URL_ROOT, $msg);
        if (ob_get_length()) {
            ob_end_clean();
        }
    }

    public function shutdown(): void
    {
        $error = error_get_last();
        if (null !== $error && $error['type'] !== E_DEPRECATED) {
            $msg = 'Error: ' . $error['message'] . PHP_EOL
                . 'date: ' . date('d.m.Y H:i:s') . PHP_EOL
                . 'file: ' . $error['file'] . ':' . $error['line'] . PHP_EOL
                . 'URI: ' . ($_SERVER['REQUEST_URI'] ?? 'undefined') . PHP_EOL
                . 'UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'none') . PHP_EOL
                . 'Cookies: ' . (isset($_COOKIE) ? print_r($_COOKIE, true) : 'none') . PHP_EOL
                . '__________________________' . PHP_EOL . PHP_EOL . PHP_EOL
                . 'trace: ' . print_r(debug_backtrace(), true) . PHP_EOL;
            mail('starkeen@gmail.com', 'Fatal error on ' . _URL_ROOT, $msg);
        }
    }
}
