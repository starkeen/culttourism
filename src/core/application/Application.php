<?php

declare(strict_types=1);

namespace app\core\application;

use app\core\ExceptionsHandler;
use app\db\FactoryDB;
use app\db\MyDB;
use app\exceptions\BaseApplicationException;
use app\sys\Logger;
use app\sys\SentryLogger;
use app\sys\TemplateEngine;
use MSysProperties;
use Throwable;

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
        error_reporting(E_ALL & ~E_DEPRECATED);

        $this->db = FactoryDB::db();

        $sentryLogger = new SentryLogger(SENTRY_DSN);
        $this->logger = new Logger($sentryLogger);

        $this->templateEngine = new TemplateEngine();
    }

    public function init(): void
    {
        set_exception_handler([ExceptionsHandler::class, 'errorsExceptionsHandler']);
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
