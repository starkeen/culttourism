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
        $fields = array('cp_text', 'cp_title', 'cp_phone');
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

    public function repairCandidatesAddrs($count = 10) {
        $log = array();
        $this->entity_type = 'candidate_points';
        $this->entity_id = 'cp_id';
        $this->entity_field = 'cp_addr';
        $dc = new MDataCheck($this->db);
        $cp = new MCandidatePoints($this->db);

        $api = new DadataAPI($this->db);
        if ($api->getBalance() == 0) {
            echo 'Баланс Dadata.ru нулевой';
            return;
        }
        $typograf = $this->buildTypograph();
        $items = $this->getCheckingPortion($count, 'cp_active', true);
        foreach ($items as $item) {
            $addr = preg_replace('/(\d{3})(\s{1})(\d{3})/', '$1$3', $item[$this->entity_field]);
            $response = $api->check(DadataAPI::ADDRESS, $addr);
            $result = $response[0]['result'];
            if ($response[0]['quality_parse'] == 0) {
                $typograf->set_text($response[0]['result']);
                $cleaned = $typograf->apply();
                $result = html_entity_decode($cleaned, ENT_QUOTES, 'UTF-8');
                $cp->updateByPk($item[$this->entity_id], array($this->entity_field => $result));

                if ($response[0]['qc_geo'] == 0) {
                    $geodata = array(
                        'cp_latitude' => $response[0]['geo_lat'],
                        'cp_longitude' => $response[0]['geo_lon'],
                    );
                    $cp->updateByPk($item[$this->entity_id], $geodata);
                }
            } else {
                $result = '[quality:' . $response[0]['quality_parse'] . '] ' . $result;
            }
            $dc->markChecked($this->entity_type, $item[$this->entity_id], $this->entity_field, $result);
        }

        return $log;
    }

    public function repairBlog($count = 10) {
        $log = array();
        $this->entity_type = 'blogentries';
        $this->entity_id = 'br_id';
        $dc = new MDataCheck($this->db);
        $be = new MBlogEntries($this->db);

        $typograf = $this->buildTypograph();
        $this->entity_field = 'br_text';
        $items = $this->getCheckingPortion($count, 'br_id');
        foreach ($items as $item) {
            $typograf->set_text($item[$this->entity_field]);
            $cleaned = $typograf->apply();
            $result = html_entity_decode($cleaned, ENT_QUOTES, 'UTF-8');
            $be->updateByPk($item[$this->entity_id], array($this->entity_field => $result));
            $dc->markChecked($this->entity_type, $item[$this->entity_id], $this->entity_field, $result);
        }

        return $log;
    }

    public function getCheckingPortion($limit, $active, $unchecked = false) {
        $checkertable = $this->db->getTableName('data_check');
        $tname = $this->db->getTableName($this->entity_type);
        $this->db->sql = "SELECT t.*
                            FROM $tname t
                                LEFT JOIN $checkertable dc ON dc.dc_item_id = t.$this->entity_id
                                    AND dc.dc_type = :item_type
                                    AND dc.dc_field = :field
                            WHERE t.$active > 0
                                AND t.$this->entity_field != ''\n";
        if ($unchecked) {
            $this->db->sql .= " AND dc.dc_id IS NULL\n";
        }
        $this->db->sql .= " GROUP BY t.$this->entity_id
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
