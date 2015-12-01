<?php

/**
 * Класс для проверки и исправления данных
 */
class DataChecker {

    protected $db;
    protected $entity_type = 'type';
    protected $entity_id = 'id';
    protected $entity_field;

    public function __construct($db) {
        $this->db = $db;
    }

    public function repairPointsAddrs($count = 100) {
        $this->entity_type = 'pagepoints';
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

    public function repairCandidates($count = 10) {
        $this->entity_type = 'candidate_points';
        $this->entity_id = 'cp_id';
        $dc = new MDataCheck($this->db);
        $cp = new MCandidatePoints($this->db);
        $log = array();

        $typograf = $this->buildTypograph();
        $fields = array('cp_text', 'cp_title',);
        foreach ($fields as $fld) {
            $this->entity_field = $fld;
            $items = $this->getCheckingPortion($count, 'cp_active');
            foreach ($items as $item) {
                $typograf->set_text($item[$this->entity_field]);
                $cleaned = $typograf->apply();
                $result = html_entity_decode($cleaned, ENT_QUOTES, 'UTF-8');
                $cp->updateByPk($item[$this->entity_id], array($this->entity_field => $result));
                $dc->markChecked($this->entity_type, $item[$this->entity_id], $this->entity_field, $result);
            }
        }

        return $log;
    }

    public function getCheckingPortion($limit, $active) {
        $checkertable = $this->db->getTableName('data_check');
        $tname = $this->db->getTableName($this->entity_type);
        $this->db->sql = "SELECT t.*
                            FROM $tname t
                                LEFT JOIN $checkertable dc ON dc.dc_item_id = t.$this->entity_id
                                    AND dc.dc_type = :item_type
                                    AND dc.dc_field = :field
                            WHERE t.$active = 1
                            GROUP BY t.$this->entity_id
                            ORDER BY dc.dc_date
                            LIMIT :limit";
        $this->db->execute(array(
            ':limit' => $limit,
            ':field' => $this->entity_field,
            ':item_type' => $this->entity_type,
        ));
        return $this->db->fetchAll();
    }

    protected function buildTypograph() {
        $typograf = new EMTypograph();
        $typograf->setup(array(
            'Text.paragraphs' => 'off',
            'Text.breakline' => 'off',
            'OptAlign.oa_oquote' => 'off',
            'OptAlign.oa_oquote_extra' => 'off',
            'OptAlign.oa_obracket_coma' => 'off',
            'Space.nbsp_before_open_quote' => 'off',
            'Space.nbsp_before_month' => 'off',
            'Nobr.super_nbsp' => 'off',
            'Nobr.nbsp_in_the_end' => 'off',
            'Nobr.phone_builder' => 'off',
            'Nobr.phone_builder_v2' => 'off',
        ));

        return $typograf;
    }

}
