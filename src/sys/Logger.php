<?php

declare(strict_types=1);

namespace app\sys;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Sentry\Severity;

/**
 * Универсальный сервис отправки логов в контексте приложения
 */
class Logger implements LoggerInterface
{
    private const SENTRY_SEND_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
    ];

    /**
     * @var SentryLogger
     */
    private $sentry;

    /**
     * @param SentryLogger $sentry
     */
    public function __construct(SentryLogger $sentry)
    {
        $this->sentry = $sentry;
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        if (in_array($level, self::SENTRY_SEND_LEVELS, true)) {
            $this->sendSentry($level, $message, $context);
        }
        Logging::addHistory($level, $message, $context);
    }

    private function sendSentry($level, $message, array $context = []):void
    {
        if ($level === LogLevel::NOTICE) {
            $severity = new Severity(LogLevel::WARNING);
        } else {
            $severity = new Severity($level);
        }

        $this->sentry->captureMessage($severity, $message, $context);
    }
}
