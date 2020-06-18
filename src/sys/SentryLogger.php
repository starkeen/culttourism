<?php

declare(strict_types=1);

namespace app\sys;

use Sentry;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\Scope;

class SentryLogger
{
    public static function init(): void
    {
        Sentry\init(
            [
                'dsn' => SENTRY_DSN,
            ]
        );

        Sentry\configureScope(
            static function (Scope $scope): void {
                $scope->setLevel(Severity::error());
            }
        );
    }

    public static function setRelease(string $key): void
    {
        $options = Hub::getCurrent()->getClient()->getOptions();

        $options->getRelease();
        $options->setRelease($key);
    }
}
