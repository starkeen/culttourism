<?php

/**
 * Класс для проверки и исправления данных
 */
class DataChecker {

    protected $db;
    protected $entity_type = 'point';
    protected $entity_field;

    public function __construct($db) {
        $this->db = $db;
    }

    public function repairPointsAddrs($count = 100) {
        $this->entity_field = 'pt_adress';
        $p = new MPagePoints($this->db);
        $dc = new MDataCheck($this->db);

        $curl = new Curl($this->db);
        $curl->setTTLDays(7);
        $curl->config(CURLOPT_TIMEOUT, 5);
        $curl->config(CURLOPT_HEADER, 0);
        $curl->config(CURLOPT_SSL_VERIFYPEER, false);

        $points = $p->getPointsWithoutAddrs($count);
        $log = array();
        foreach ($points as $i => $pt) {
            $request = 'https://geocode-maps.yandex.ru/1.x/?format=json'
                    . '&geocode=' . $pt['pt_longitude'] . ',' . $pt['pt_latitude']
                    . '&ll=37.618920,55.756994'
                    . '&kind=house&results=1';
            $answer = $curl->get($request);
            $data = json_decode($answer);
            if (empty($data->response->GeoObjectCollection)) {
                break;
            }
            $founded = $data->response->GeoObjectCollection->metaDataProperty->GeocoderResponseMetaData->found > 0;
            $featureMember = $data->response->GeoObjectCollection->featureMember;
            $addr_variant = array(
                'text' => $pt['pt_adress'],
                'delta_meters' => 100500,
            );
            foreach ($featureMember as $fm) {
                $latlon = explode(' ', $fm->GeoObject->Point->pos);
                $addr_variant = array(
                    'text' => $fm->GeoObject->metaDataProperty->GeocoderMetaData->text,
                    'gps' => array(
                        'latitude' => $latlon[1],
                        'longitude' => $latlon[0],
                    ),
                    'delta_lat' => round(abs($pt['pt_latitude'] - $latlon[1]), 5),
                    'delta_lon' => round(abs($pt['pt_longitude'] - $latlon[0]), 5),
                    'delta_meters' => Helper::distanceGPS($pt['pt_latitude'], $pt['pt_longitude'], $latlon[1], $latlon[0]),
                );
            }
            if ($founded && $addr_variant['delta_meters'] < 10) {
                $p->updateByPk($pt['pt_id'], array(
                    'pt_adress' => $addr_variant['text'],
                ));
                $log[] = array(
                    $pt['pt_id'],
                    $pt['pt_name'],
                    $pt['pt_adress'],
                    $addr_variant['text'],
                    round($addr_variant['delta_meters'], 2),
                );
                $dc->markChecked($this->entity_type, $pt['pt_id'], $this->entity_field, $addr_variant['text']);
            } else {
                $dc->markChecked($this->entity_type, $pt['pt_id'], $this->entity_field, 'default: ' . $addr_variant['delta_meters']);
            }
        }
        return $log;
    }

}
