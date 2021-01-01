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
    private ?MyDB $db = null;

    private ?Logger $logger = null;

    private ?TemplateEngine $templateEngine = null;

    private ?MSysProperties $sysProperties = null;

    public function __construct()
    {
        error_reporting(E_ALL);

        $exceptionHandler = new ExceptionsHandler($this->getLogger());
        set_exception_handler([$exceptionHandler, 'errorsExceptionsHandler']);
        register_shutdown_function([$exceptionHandler, 'shutdown']);
        set_error_handler(static function ($severity, $message, $filename, $lineno) {
            throw new ErrorException($message, 0, $severity, $filename, $lineno);
        });
    }

    public function getLogger(): Logger
    {
        if ($this->logger === null) {
            $sentryLogger = new SentryLogger(SENTRY_DSN);
            $this->logger = new Logger($sentryLogger);
        }

        return $this->logger;
    }

    public function init(): void
    {
        $releaseKey = $this->getSysPropertiesModel()->getByName('git_hash');
        $this->getLogger()->setReleaseKey($releaseKey);
    }

    protected function getSysPropertiesModel(): MSysProperties
    {
        if ($this->sysProperties === null) {
            $this->sysProperties = new MSysProperties($this->getDb());
        }
        return $this->sysProperties;
    }

    public function setSysPropertiesModel(MSysProperties $sysProperties): void
    {
        $this->sysProperties = $sysProperties;
    }

    /**
     * @return MyDB
     */
    protected function getDb(): MyDB
    {
        if ($this->db === null) {
            $this->db = FactoryDB::db();
        }

        return $this->db;
    }

    public function setDb(MyDB $db): void
    {
        $this->db = $db;
    }

    /**
     * @return TemplateEngine
     */
    protected function getTemplateEngine(): TemplateEngine
    {
        if ($this->templateEngine === null) {
            $this->templateEngine = new TemplateEngine();
        }

        return $this->templateEngine;
    }

    public function setTemplateEngine(TemplateEngine $templateEngine): void
    {
        $this->templateEngine = $templateEngine;
    }

    abstract public function run(): void;
}
