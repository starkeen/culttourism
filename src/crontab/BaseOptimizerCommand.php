<?php

declare(strict_types=1);

namespace app\crontab;

use MAuthorizations;
use MCurlCache;
use MLogActions;
use MLogErrors;
use MNewsItems;

class BaseOptimizerCommand extends AbstractCrontabCommand
{
    private MCurlCache $curlCache;
    private MAuthorizations $authorizations;
    private MLogActions $logActions;
    private MLogErrors $logErrors;
    private MNewsItems $newsItems;

    public function __construct(MCurlCache $cc, MAuthorizations $au, MLogActions $la, MLogErrors $le, MNewsItems $ni)
    {
        $this->curlCache = $cc;
        $this->authorizations = $au;
        $this->logActions = $la;
        $this->logErrors = $le;
        $this->newsItems = $ni;
    }

    public function run(): void
    {
        $this->curlCache->cleanExpired();

        $this->authorizations->cleanExpired();
        $this->authorizations->cleanUnused();

        $this->logActions->cleanExpired();

        $this->logErrors->cleanExpired();

        $this->newsItems->cleanExpired();
    }
}
