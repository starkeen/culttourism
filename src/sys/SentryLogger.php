<?php

declare(strict_types=1);

namespace app\sys;

use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\State\Scope;
use Sentry;

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
    }

    public function setReleaseKey(string $key): void
    {
        $options = $this->client->getOptions();

        $options->setRelease($key);
    }

    /**
     * @param Severity $level
     * @param string $message
     * @param array $context
     *
     * @return string|null
     */
    public function captureMessage(Severity $level, string $message, array $context): ?string
    {
        $hub = SentrySdk::getCurrentHub();
        $hub->configureScope(
            static function (Scope $scope) use ($context): void {
                $scope->setTags($context);
                $scope->setExtras($context);
            }
        );
        return $hub->captureMessage($message, $level);
    }
}
