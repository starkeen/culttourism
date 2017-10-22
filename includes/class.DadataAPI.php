<?php

use app\db\MyDB;

class DadataAPI
{
    const ADDRESS = 'address';
    const PHONE = 'phone';
    const BALANCE = 'balance';

    protected $url = 'https://dadata.ru/api/v2/clean/';
    protected $keyToken;
    protected $keySecret;
    protected $fields = [
        self::ADDRESS => [
            'direct' => [
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
            'direct' => [
                'source',
                'phone',
            ],
        ],
    ];
    protected $curl;

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
     * @throws RuntimeException
     */
    public function check($type, $data): array
    {
        if (!array_key_exists($type, $this->fields)) {
            throw new RuntimeException('Unsupported checking type');
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
        $out = $this->curl->post($this->url . $type, $json);

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
            foreach ((array) $this->fields[$type]['direct'] as $field) {
                $item[$field] = $r->$field;
            }
            $item['quality_parse'] = $r->qc;
            $out[] = $item;
        }

        return $out;
    }

}
