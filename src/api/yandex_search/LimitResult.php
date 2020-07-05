<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use DateInterval;
use DateTime;
use DateTimeZone;
use SimpleXMLElement;

class LimitResult
{
    /**
     * @var SimpleXMLElement
     */
    private $xml;

    public function __construct(string $responseText)
    {
        $this->xml = new SimpleXMLElement($responseText);
    }

    public function getCurrentLimit(): int
    {
        $result = 0;
        $now = new DateTime();
        $intervals = $this->xml->xpath('response/limits/time-interval');
        foreach($intervals as $interval) {
            $from = (string) $interval['from'];
            $to = (string) $interval['to'];
            $dateFrom = new DateTime($from, new DateTimeZone('Europe/Moscow'));
            $dateFrom->add(new DateInterval('PT3H'));
            $dateTo = new DateTime($to, new DateTimeZone('Europe/Moscow'));
            $dateTo->add(new DateInterval('PT3H'));
            $diffFrom = (int) $dateFrom->diff($now)->format('%H');
            $diffTo = (int) $dateTo->diff($now)->format('%H');
            if ($diffFrom === 1 && $diffTo === 0) {
                $result = (int) $interval;
                break;
            }
        }

        return $result;
    }
}
