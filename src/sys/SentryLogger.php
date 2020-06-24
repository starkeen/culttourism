<?php

declare(strict_types=1);

namespace app\sys;

use InvalidArgumentException;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\Options;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

class SentryLogger
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var HubInterface
     */
    private $hub;

    /**
     * @param string $dsn
     */
    public function __construct(string $dsn)
    {
        $options = new Options(
            [
                'dsn' => $dsn,
                'capture_silenced_errors' => true,
            ]
        );
        $clientBuilder = new ClientBuilder($options);
        $this->client = $clientBuilder->getClient();

        $this->hub = new Hub();
        $this->hub->bindClient($this->client);
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

        $this->hub->configureScope(
            static function (Scope $scope) use ($plainContext): void {
                $scope->setTags($plainContext);
                $scope->setExtras($plainContext);
            }
        );

        return $this->hub->captureMessage($message, $level);
    }

    /**
     * @param array $context
     * @param string|null $prefix
     *
     * @return array
     */
    public function plainContext(array $context, string $prefix = null): array
    {
        $result = [];
        foreach($context as $key => $value) {
            $resultKey = $key;
            if ($prefix !== null) {
                $resultKey = $prefix . '_' . $resultKey;
            }
            if (is_bool($value)) {
                $result += [$resultKey => $value ? '<true>' : '<false>'];
            } elseif (is_scalar($value)) {
                $result += [$resultKey => (string) $value];
            } elseif ($value === null) {
                $result += [$resultKey => '<null>'];
            } elseif (is_array($value)) {
                $result += $this->plainContext($value, $key);
            } elseif (is_object($value)) {
                $result += $this->plainContext([$resultKey => json_encode($value)]);
            } else {
                throw new InvalidArgumentException('Недоступный для сериализации контекст: key=' . $key);
            }
        }

        return array_merge($result);
    }
}
