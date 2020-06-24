<?php

declare(strict_types=1);

namespace app\sys;

use InvalidArgumentException;
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
    public function captureMessage(Severity $level, string $message, array $context = []): ?string
    {
        $plainContext = $this->plainContext($context);

        $hub = SentrySdk::getCurrentHub();
        $hub->configureScope(
            static function (Scope $scope) use ($plainContext): void {
                $scope->setTags($plainContext);
                $scope->setExtras($plainContext);
            }
        );
        return $hub->captureMessage($message, $level);
    }

    /**
     * @param array $context
     * @param string|null $prefix
     *
     * @return array
     */
    private function plainContext(array $context, string $prefix = null): array
    {
        $result = [];
        foreach($context as $key => $value) {
            $resultKey = $key;
            if ($prefix !== null) {
                $resultKey = $prefix . '_' . $resultKey;
            }
            if (is_scalar($value)) {
                $result[$resultKey] = $value;
            } elseif (is_array($value)) {
                $result[$resultKey] = $this->plainContext($value, $key);
            } elseif (is_object($value)) {
                $result[$resultKey] = $this->plainContext((array) $value, $key);
            } else {
                throw new InvalidArgumentException('Недоступный для сериализации контекст');
            }
        }

        return $result;
    }
}
