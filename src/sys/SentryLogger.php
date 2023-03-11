<?php

declare(strict_types=1);

namespace app\sys;

use app\exceptions\BaseApplicationException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Psr\Log\LogLevel;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\Options;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Throwable;

class SentryLogger
{
    public const CRON_MONITORING_RUN = 'in_progress';
    public const CRON_MONITORING_DONE = 'ok';
    public const CRON_MONITORING_FAIL = 'error';

    private const VALID_STATUSES = [self::CRON_MONITORING_FAIL, self::CRON_MONITORING_DONE, self::CRON_MONITORING_RUN];

    private ClientInterface $client;

    private HubInterface|Hub $hub;
    private Client $guzzleClient;
    private string $organizationSlug;
    private string $sentryDSN;

    /**
     * @param string $dsn
     */
    public function __construct(Client $guzzleClient, string $dsn, string $organizationSlug)
    {
        $this->guzzleClient = $guzzleClient;
        $this->sentryDSN = $dsn;
        $this->organizationSlug = $organizationSlug;

        $options = new Options(
            [
                'dsn' => $this->sentryDSN,
                'capture_silenced_errors' => true,
                'traces_sample_rate' => 0.2,
                'environment' => GLOBAL_ERROR_REPORTING ? 'developer' : 'production',
                'send_default_pii' => true,
            ]
        );
        $clientBuilder = new ClientBuilder($options);
        $this->client = $clientBuilder->getClient();

        $this->hub = new Hub();
        SentrySdk::setCurrentHub($this->hub);
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
                $scope->setExtras($plainContext);
            }
        );

        $eventId = $this->hub->captureMessage($message, $level);

        return (string) $eventId;
    }

    /**
     * @param Throwable $exception
     * @return string|null
     */
    public function captureException(Throwable $exception): ?string
    {
        return (string) $this->hub->captureException($exception);
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
            $key = (string) $key;
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

    public function cronMonitoringSend(string $monitorId, string $status, int $duration = null): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new BaseApplicationException('Unexpected status');
        }

        $this->send($monitorId, $status, $duration);
    }

    private function send(string $monitorId, string $status, ?int $duration = null): void
    {
        $payload = ['status' => $status];
        if ($duration !== null) {
            $payload['duration'] = $duration;
        }

        $this->guzzleClient->post(
            $this->getUrl($monitorId),
            [
                RequestOptions::JSON => $payload,
                RequestOptions::HEADERS => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'DSN ' . $this->sentryDSN,
                ],
            ]
        );
    }

    private function getUrl(string $monitorId): string
    {
        return sprintf(
            'https://sentry.io/api/0/organizations/%s/monitors/%s/checkins/',
            $this->organizationSlug,
            $monitorId
        );
    }
}
