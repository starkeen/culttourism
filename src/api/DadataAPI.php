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
    public const TYPE_BALANCE = 'balance';

    private const KEY_DIRECT = 'direct';

    private const BASE_URL = 'https://dadata.ru/api/v2/clean/';

    /**
     * @var string
     */
    private $keyToken;

    /**
     * @var string
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
        self::PHONE => [
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
        $sp = new MSysProperties($db);
        $this->keyToken = $sp->getByName('app_dadata_token');
        $this->keySecret = $sp->getByName('app_dadata_secret');
        $this->curl = new Curl($db);
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
        $this->curl->addHeader('Authorization', 'Token ' . $this->keyToken);
        $this->curl->addHeader('X-Secret', $this->keySecret);
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
    protected function request($type, $context)
    {
        $json = json_encode($context);

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Token ' . $this->keyToken);
        $this->curl->addHeader('X-Secret', $this->keySecret);
        $this->curl->config(CURLOPT_SSL_VERIFYPEER, false);
        $out = $this->curl->post(self::BASE_URL . $type, $json);

        return json_decode($out);
    }

    /**
     * @param string $type
     * @param        $response
     *
     * @return array
     */
    protected function mapResponse($type, $response): array
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
