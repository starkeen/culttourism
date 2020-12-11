<?php

declare(strict_types=1);

namespace app\core;

use app\db\FactoryDB;
use app\db\MyDB;
use app\sys\Logger;
use app\sys\SentryLogger;

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

    public function __construct()
    {
        error_reporting(E_ALL & ~E_DEPRECATED);

        $this->db = FactoryDB::db();

        $sentryLogger = new SentryLogger(SENTRY_DSN);
        $this->logger = new Logger($sentryLogger);
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
}
