<?php

class DadataAPI {

    const ADDRESS = 'address';
    const PHONE = 'phone';

    protected $url = 'https://dadata.ru/api/v2/clean/';
    protected $keyToken;
    protected $keySecret;
    protected $fields = array(
        self::ADDRESS => array(
            'direct' => array('source', 'result', 'geo_lat', 'geo_lon', 'qc_geo', 'unparsed_parts',),
        ),
        self::PHONE => array(
            'direct' => array('source', 'phone',),
        ),
    );
    protected $curl;

    public function __construct(MyDB $db) {
        $sp = new MSysProperties($db);
        $this->keyToken = $sp->getByName('app_dadata_token');
        $this->keySecret = $sp->getByName('app_dadata_secret');
        $this->curl = new Curl($db);
    }

    public function check($type, $data) {
        if (!in_array($type, array_keys($this->fields))) {
            throw new RuntimeException('Unsupported checking type');
        }
        $context = is_array($data) ? $data : array($data);
        $response = $this->request($type, $context);

        return $this->mapResponse($type, $response);
    }

    /**
     * 
     * @param string $type
     * @param mixed $context
     * @return array
     */
    protected function request($type, $context) {
        $json = json_encode($context);

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Token ' . $this->keyToken);
        $this->curl->addHeader('X-Secret', $this->keySecret);
        $this->curl->config(CURLOPT_SSL_VERIFYPEER, false);
        $out = $this->curl->post($this->url . $type, $json);

        return json_decode($out);
    }

    protected function mapResponse($type, $response) {
        $out = array();
        foreach ($response as $r) {
            $item = array();
            foreach ($this->fields[$type]['direct'] as $field) {
                $item[$field] = $r->$field;
            }
            $item['quality_parse'] = $r->qc;
            $out[] = $item;
        }
        return $out;
    }

}
