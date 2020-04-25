<?php

declare(strict_types=1);

namespace app\api;

use app\db\MyDB;
use Curl;
use InvalidArgumentException;
use MSysProperties;
use stdClass;

class DadataAPI
{
    public const TYPE_ADDRESS = 'address';
    public const TYPE_PHONE = 'phone';

    private const KEY_DIRECT = 'direct';

    private const BASE_URL = 'https://dadata.ru/api/v2/clean/';

    /**
     * @var MSysProperties
     */
    private $sysProperties;

    /**
     * @var string|null
     */
    private $keyToken;

    /**
     * @var string|null
     */
    private $keySecret;

    private const FIELDS = [
        self::TYPE_ADDRESS => [
            self::KEY_DIRECT => [
                'source',
                'result',
                'geo_lat',
                'geo_lon',
                'qc',
                'qc_geo',
                'qc_complete',
                'qc_house',
                'unparsed_parts',
            ],
        ],
        self::TYPE_PHONE => [
            self::KEY_DIRECT => [
                'source',
                'phone',
            ],
        ],
    ];

    /**
     * @var Curl
     */
    private $curl;

    /**
     *
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->sysProperties = new MSysProperties($db);
        $this->curl = new Curl($db);
    }

    private function getToken(): string
    {
        if ($this->keyToken === null) {
            $this->keyToken = $this->keyToken = $this->sysProperties->getByName('app_dadata_token');
        }

        return $this->keyToken;
    }

    private function getSecret(): string
    {
        if ($this->keySecret === null) {
            $this->keySecret = $this->sysProperties->getByName('app_dadata_secret');
        }

        return $this->keySecret;
    }

    /**
     * @param $type
     * @param $data
     *
     * @return array
     */
    public function check($type, $data): array
    {
        if (!array_key_exists($type, self::FIELDS)) {
            throw new InvalidArgumentException('Unsupported checking type');
        }
        $context = is_array($data) ? $data : [$data];
        $response = $this->request($type, $context);

        return $this->mapResponse($type, $response);
    }

    /**
     * @return float
     */
    public function getBalance(): float
    {
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Token ' . $this->getToken());
        $this->curl->addHeader('X-Secret', $this->getSecret());
        $this->curl->config(CURLOPT_SSL_VERIFYPEER, false);

        $this->curl->setTTL(0);
        $json = $this->curl->get('https://dadata.ru/api/v2/profile/balance');
        $data = json_decode($json);

        return (float) $data->balance;
    }

    /**
     *
     * @param string $type
     * @param mixed  $context
     *
     * @return array|stdClass
     */
    private function request($type, $context)
    {
        $json = json_encode($context);

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Token ' . $this->getToken());
        $this->curl->addHeader('X-Secret', $this->getSecret());
        $this->curl->config(CURLOPT_SSL_VERIFYPEER, false);
        $out = $this->curl->post(self::BASE_URL . $type, $json);

        return json_decode($out);
    }

    /**
     * @param string $type
     * @param mixed $response
     *
     * @return array
     */
    private function mapResponse(string $type, $response): array
    {
        $out = [];
        foreach ((array) $response as $r) {
            $item = [];
            foreach (self::FIELDS[$type][self::KEY_DIRECT] ?? [] as $field) {
                $item[$field] = $r->$field;
            }
            $item['quality_parse'] = $r->qc;
            $out[] = $item;
        }

        return $out;
    }
}
