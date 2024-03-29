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

        if (PHP_SAPI === 'cli') {
            echo $msg;
        } else {
            mail('starkeen@gmail.com', 'Error on ' . GLOBAL_URL_ROOT, $msg);
            if (ob_get_length()) {
                ob_end_clean();
            }
        }
        $this->logger->sendSentryException($exception);
    }

    /**
     * Обработка фатальных ошибок, отловленных на этапе окончания работы
     */
    public function shutdown(): void
    {
        $error = error_get_last();
        if (null !== $error) {
            $msg = 'Error: ' . $error['message'] . PHP_EOL
                . 'date: ' . date('d.m.Y H:i:s') . PHP_EOL
                . 'file: ' . $error['file'] . ':' . $error['line'] . PHP_EOL
                . 'URI: ' . ($_SERVER['REQUEST_URI'] ?? 'undefined') . PHP_EOL
                . 'UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'none') . PHP_EOL
                . 'Cookies: ' . (isset($_COOKIE) ? print_r($_COOKIE, true) : 'none') . PHP_EOL
                . '__________________________' . PHP_EOL . PHP_EOL . PHP_EOL
                . 'trace: ' . print_r(debug_backtrace(), true) . PHP_EOL;
            if (PHP_SAPI === 'cli') {
                echo $msg;
            } else {
                mail('starkeen@gmail.com', 'Fatal error on ' . GLOBAL_URL_ROOT, $msg);
            }
        }
    }
}
