<?php

declare(strict_types=1);

namespace app\core\application;

use app\core\ExceptionsHandler;
use app\db\FactoryDB;
use app\db\MyDB;
use app\sys\Logger;
use app\sys\SentryLogger;
use app\sys\TemplateEngine;
use ErrorException;
use MSysProperties;

abstract class Application
{
    /**
     * @var MyDB
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TemplateEngine
     */
    protected $templateEngine;

    public function __construct()
    {
        error_reporting(E_ALL);
        $sentryLogger = new SentryLogger(SENTRY_DSN);
        $this->logger = new Logger($sentryLogger);

        $exceptionHandler = new ExceptionsHandler($this->logger);
        set_exception_handler([$exceptionHandler, 'errorsExceptionsHandler']);
        register_shutdown_function([$exceptionHandler, 'shutdown']);
        set_error_handler(static function ($severity, $message, $filename, $lineno) {
            throw new ErrorException($message, 0, $severity, $filename, $lineno);
        });

        $this->db = FactoryDB::db();

        $this->templateEngine = new TemplateEngine();
    }

    public function init(): void
    {
        $sp = new MSysProperties($this->db);
        $releaseKey = $sp->getByName('git_hash');
        $this->logger->setReleaseKey($releaseKey);
    }

    abstract public function run(): void;

    /**
     * @deprecated
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * @deprecated
     * @return MyDB
     */
    public function getDb(): MyDB
    {
        return $this->db;
    }

    /**
     * @deprecated
     * @return TemplateEngine
     */
    public function getTemplateEngine(): TemplateEngine
    {
        return $this->templateEngine;
    }
}
