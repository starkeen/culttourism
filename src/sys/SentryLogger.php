<?php

declare(strict_types=1);

namespace app\sys;

use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\State\Scope;

class SentryLogger
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param string $dsn
     */
    public function __construct(string $dsn)
    {
        $this->client = ClientBuilder::create(
            [
                'dsn' => $dsn,
                'capture_silenced_errors' => true,
            ]
        )->getClient();

        $hub = SentrySdk::init();
        $hub->bindClient($this->client);
        $hub->configureScope(
            static function (Scope $scope): void {
                $scope->setLevel(Severity::error());
            }
        );
    }

    public function setReleaseKey(string $key): void
    {
        $options = $this->client->getOptions();

        $options->setRelease($key);
    }

    /**
     * @param string $message
     * @param Severity|null $level
     *
     * @return string|null
     */
    public function captureMessage(string $message, ?Severity $level = null): ?string
    {
        return SentrySdk::getCurrentHub()->captureMessage($message, $level);
    }
}
