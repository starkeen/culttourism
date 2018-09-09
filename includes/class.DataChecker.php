<?php

use app\exceptions\MyPDOException;
use models\MPhones;

/**
 * Класс для проверки и исправления данных
 */
class DataChecker
{
    protected $db;
    protected $entity_type = 'type';
    protected $entity_id = 'id';
    protected $entity_field;
    protected $dotting = [
        ' г ' => ' г. ',
        ' пос ' => ' пос. ',
        ' ул ' => ' ул. ',
        ' пл ' => ' пл. ',
        ' ш ' => ' ш. ',
        ' им ' => ' им. ',
        ' д ' => ' д. ',
    ];

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairPointsAddrs($count = 100): array
    {
        $this->entity_type = MDataCheck::ENTITY_POINTS;
        $this->entity_id = 'pt_id';
        $this->entity_field = 'pt_adress';
        $p = new MPagePoints($this->db);
        $dc = new MDataCheck($this->db);

        $curl = new Curl($this->db);
        $curl->setTTLDays(7);
        $curl->config(CURLOPT_TIMEOUT, 5);
        $curl->config(CURLOPT_HEADER, 0);
        $curl->config(CURLOPT_SSL_VERIFYPEER, false);

        $points = $p->getPointsWithoutAddrs($count);
        $log = [];
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
            $addrVariant = [
                'text' => $pt['pt_adress'],
                'delta_meters' => 100500,
            ];
            foreach ($featureMember as $fm) {
                $latlon = explode(' ', $fm->GeoObject->Point->pos);
                $addrVariant = [
                    'text' => isset($fm->GeoObject->metaDataProperty->GeocoderMetaData) ? $fm->GeoObject->metaDataProperty->GeocoderMetaData->text : $fm->GeoObject->name,
                    'gps' => [
                        'latitude' => $latlon[1],
                        'longitude' => $latlon[0],
                    ],
                    'delta_lat' => round(abs($pt['pt_latitude'] - $latlon[1]), 5),
                    'delta_lon' => round(abs($pt['pt_longitude'] - $latlon[0]), 5),
                    'delta_meters' => Helper::distanceGPS(
                        $pt['pt_latitude'],
                        $pt['pt_longitude'],
                        $latlon[1],
                        $latlon[0]
                    ),
                ];
            }
            if ($founded && $addrVariant['delta_meters'] < 10) {
                $p->updateByPk(
                    $pt['pt_id'],
                    [
                        'pt_adress' => $addrVariant['text'],
                    ]
                );
                $log[] = [
                    $pt['pt_id'],
                    $pt['pt_name'],
                    $pt['pt_adress'],
                    $addrVariant['text'],
                    round($addrVariant['delta_meters'], 2),
                ];
                $dc->markChecked($this->entity_type, $pt[$this->entity_id], $this->entity_field, $addrVariant['text']);
            } else {
                $dc->markChecked(
                    $this->entity_type,
                    $pt['pt_id'],
                    $this->entity_field,
                    'default: ' . $addrVariant['delta_meters']
                );
            }
        }

        return $log;
    }


    /**
     * @param int $count
     *
     * @return array
     * @throws MyPDOException
     */
    public function repairPointsPhones(int $count = 10): array
    {
        $log = [];

        $this->entity_type = MDataCheck::ENTITY_POINTS;
        $this->entity_id = 'pc_id';
        $this->entity_field = 'pt_phone';

        $dataChecker = new MDataCheck($this->db);
        $ptModel = new MPagePoints($this->db);
        $phonesModel = new MPhones($this->db);

        $points = $ptModel->getPointsWithPhones($count);
        foreach ($points as $i => $pt) {
            $logItem = [];
            $pointId = (int)$pt['pt_id'];
            $phoneString = $pt['pt_phone'];
            $phoneItems = explode(',', $phoneString);

            $phonesModel->deleteByPoint($pointId);
            foreach ($phoneItems as $phoneItem) {
                $phoneItem = trim($phoneItem);
                $phonesModel->insert(
                    [
                        'phone_raw' => $phoneItem,
                        'id_point' => $pointId,
                        'id_city' => (int)$pt['pc_id'],
                    ]
                );
            }
            $phonesModel->process();

            // исправление формата телефонов
            $newPhones = trim($phoneString);
            if ($newPhones !== $phoneString) {
                $ptModel->updateByPk($pointId, [
                    'pt_phone' => $newPhones,
                ]);
                $logItem = [
                    'pt_id' => $pointId,
                    'old_phone' => $phoneString,
                    'new_phone' => $newPhones,
                ];
            }

            $dataChecker->markChecked(
                $this->entity_type,
                $pt['pt_id'],
                $this->entity_field,
                print_r($logItem, true)
            );
            if ($logItem !== []) {
                $log[] = $logItem;
            }
        }

        return $log;
    }

    /**
     * Указание координат по адресу
     *
     * @param int $count
     *
     * @return array
     * @throws RuntimeException
     */
    public function repairPointsCoordinates(int $count = 10): array
    {
        $log = [];

        $this->entity_type = MDataCheck::ENTITY_POINTS;
        $this->entity_id = 'pt_id';
        $this->entity_field = 'pt_latitude';
        $p = new MPagePoints($this->db);
        $dc = new MDataCheck($this->db);

        $api = new DadataAPI($this->db);
        if ($api->getBalance() < 5.0) {
            echo 'Баланс Dadata.ru нулевой';
            return [];
        }

        $curl = new Curl($this->db);
        $curl->setTTLDays(7);
        $curl->config(CURLOPT_TIMEOUT, 5);
        $curl->config(CURLOPT_HEADER, 0);
        $curl->config(CURLOPT_SSL_VERIFYPEER, false);

        $points = $p->getPointsWithoutCoordinates($count);
        foreach ($points as $i => $pt) {
            $addr = preg_replace('/(\d{3})(\s{1})(\d{3})/', '$1$3', $pt['pt_adress']);
            $response = $api->check(DadataAPI::ADDRESS, $addr);
            $result = $response[0];
            $coordinates = '';
            if ((int) $result['qc'] === 0 && (int) $result['qc_geo'] === 0 && (float) $result['geo_lat'] !== 0 && (float) $result['geo_lon'] !== 0) {
                $coordinates = sprintf('%f, %f', $result['geo_lat'], $result['geo_lon']);
                $geoData = [
                    'pt_latitude' => (float) $result['geo_lat'],
                    'pt_longitude' => (float) $result['geo_lon'],
                ];
                $p->updateByPk($pt[$this->entity_id], $geoData);
                $log[] = [
                    $pt['pt_id'],
                    $pt['pt_name'],
                    $pt['pt_adress'],
                    $result['result'],
                    $coordinates,
                ];
            }

            $dc->markChecked($this->entity_type, $pt[$this->entity_id], $this->entity_field, $coordinates);
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairCandidates($count = 10): array
    {
        $this->entity_type = MDataCheck::ENTITY_CANDIDATES;
        $this->entity_id = 'cp_id';
        $dc = new MDataCheck($this->db);
        $cp = new MCandidatePoints($this->db);
        $log = [];

        $typograph = $this->buildTypograph();
        $fields = ['cp_text', 'cp_title', 'cp_phone'];
        foreach ($fields as $fld) {
            $this->entity_field = $fld;
            $items = $this->getCheckingPortion($count, 'cp_active');
            foreach ($items as $item) {
                $typograph->set_text($item[$this->entity_field]);
                $cleaned = $typograph->apply();
                $cleaned = $this->repairTypographErrors($cleaned);
                $result = html_entity_decode($cleaned, ENT_QUOTES, 'UTF-8');
                $result = ($fld === 'cp_phone') ? str_replace('−', '-', $result) : $result;
                $cp->updateByPk($item[$this->entity_id], [$this->entity_field => $result]);
                $dc->markChecked($this->entity_type, $item[$this->entity_id], $this->entity_field, $result);
            }
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairCandidatesAddrs($count = 10): array
    {
        $log = [];
        $this->entity_type = MDataCheck::ENTITY_CANDIDATES;
        $this->entity_id = 'cp_id';
        $this->entity_field = 'cp_addr';
        $dc = new MDataCheck($this->db);
        $cp = new MCandidatePoints($this->db);

        $api = new DadataAPI($this->db);
        if ($api->getBalance() < 5.0) {
            echo 'Баланс Dadata.ru нулевой';
            return [];
        }
        $typograph = $this->buildTypograph();
        $items = $this->getCheckingPortion($count, 'cp_active', true);
        foreach ($items as $item) {
            $addr = preg_replace('/(\d{3})(\s{1})(\d{3})/', '$1$3', $item[$this->entity_field]);
            $response = $api->check(DadataAPI::ADDRESS, $addr);
            $result = $response[0]['result'];
            if ($response[0]['quality_parse'] == 0) {
                $dotted = str_replace(array_keys($this->dotting), array_values($this->dotting), $response[0]['result']);
                $typograph->set_text($dotted);
                $cleaned = $typograph->apply();
                $result = html_entity_decode($cleaned, ENT_QUOTES, 'UTF-8');
                $cp->updateByPk($item[$this->entity_id], [$this->entity_field => $result]);

                if ($item['cp_latitude'] == 0 && $item['cp_longitude'] == 0 && $response[0]['qc_geo'] == 0) {
                    $geodata = [
                        'cp_latitude' => $response[0]['geo_lat'],
                        'cp_longitude' => $response[0]['geo_lon'],
                    ];
                    $cp->updateByPk($item[$this->entity_id], $geodata);
                }
            } else {
                $result = '[quality:' . $response[0]['quality_parse'] . '] ' . $result;
            }
            $dc->markChecked($this->entity_type, $item[$this->entity_id], $this->entity_field, $result);
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairBlog($count = 10): array
    {
        $log = [];
        $this->entity_type = MDataCheck::ENTITY_BLOG;
        $this->entity_id = 'br_id';
        $dc = new MDataCheck($this->db);
        $be = new MBlogEntries($this->db);

        $typograph = $this->buildTypograph();
        $this->entity_field = 'br_text';
        $items = $this->getCheckingPortion($count, 'br_id');
        foreach ($items as $item) {
            $typograph->set_text($item[$this->entity_field]);
            $cleaned = $typograph->apply();
            $cleaned = $this->repairTypographErrors($cleaned);
            $result = html_entity_decode($cleaned, ENT_QUOTES, 'UTF-8');
            $be->updateByPk($item[$this->entity_id], [$this->entity_field => $result]);
            $dc->markChecked($this->entity_type, $item[$this->entity_id], $this->entity_field, $result);
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairPoints($count = 10): array
    {
        $log = [];
        $this->entity_type = MDataCheck::ENTITY_POINTS;
        $this->entity_id = 'pt_id';
        $dc = new MDataCheck($this->db);
        $pt = new MPagePoints($this->db);

        $typograph = $this->buildTypograph();
        $fields = ['pt_name', 'pt_description', 'pt_adress'];
        foreach ($fields as $fld) {
            $this->entity_field = $fld;
            $items = $this->getCheckingPortion($count, 'pt_active');
            foreach ($items as $item) {
                $typograph->set_text($item[$this->entity_field]);
                $cleaned = $typograph->apply();
                $cleaned = $this->repairTypographErrors($cleaned);
                $result = html_entity_decode($cleaned, ENT_QUOTES, 'UTF-8');
                $pt->updateByPk($item[$this->entity_id], [$this->entity_field => $result]);
                $dc->markChecked($this->entity_type, $item[$this->entity_id], $this->entity_field, $result);
            }
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairCity($count = 10): array
    {
        $log = [];
        $this->entity_type = MDataCheck::ENTITY_CITIES;
        $this->entity_id = 'pc_id';
        $dc = new MDataCheck($this->db);
        $pc = new MPageCities($this->db);

        $typograf = $this->buildTypograph();
        $fields = ['pc_text', 'pc_announcement', 'pc_description'];
        foreach ($fields as $fld) {
            $this->entity_field = $fld;
            $items = $this->getCheckingPortion($count, 'pc_active');
            foreach ($items as $item) {
                $typograf->set_text($item[$this->entity_field]);
                $cleaned = $typograf->apply();
                $cleaned = $this->repairTypographErrors($cleaned);
                $result = html_entity_decode($cleaned, ENT_QUOTES, 'UTF-8');
                $pc->updateByPk($item[$this->entity_id], [$this->entity_field => $result]);
                $dc->markChecked($this->entity_type, $item[$this->entity_id], $this->entity_field, $result);
            }
        }

        return $log;
    }

    /**
     * @param int $limit
     * @param bool $active
     * @param bool $unchecked
     *
     * @return array
     */
    public function getCheckingPortion($limit, $active, $unchecked = false): array
    {
        $checkerTable = $this->db->getTableName('data_check');
        $tableName = $this->db->getTableName($this->entity_type);
        $this->db->sql = "SELECT t.*
                            FROM $tableName t
                                LEFT JOIN $checkerTable dc ON dc.dc_item_id = t.$this->entity_id
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
        $this->db->execute(
            [
                ':limit' => $limit,
                ':field' => $this->entity_field,
                ':item_type' => $this->entity_type,
            ]
        );

        return $this->db->fetchAll();
    }

    /**
     * @return EMTypograph
     */
    protected function buildTypograph(): EMTypograph
    {
        $typograph = new EMTypograph();
        $typograph->setup(
            [
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
                'Nobr.spaces_nobr_in_surname_abbr' => 'off',
            ]
        );

        return $typograph;
    }

    /**
     * Исправляет в тексте ошибки, допущенные типографом
     *
     * @param string $text
     *
     * @return string
     */
    protected function repairTypographErrors($text): string
    {
        $from = ['вв.</nobr>ек', '<nobr>', '</nobr>'];
        $to = ['век', '', ''];

        return str_replace($from, $to, $text);
    }

    /**
     * @param string $type
     * @param string $field
     * @param int $ageDays
     */
    public function resetOldData(string $type, string $field, int $ageDays): void
    {
        $checkerTable = $this->db->getTableName('data_check');
        $this->db->sql = "DELETE FROM $checkerTable
                            WHERE dc_type = :item_type
                                AND dc_field = :field
                                AND dc_result = ''
                                AND dc_date <= DATE_SUB(NOW(), INTERVAL :date_before DAY)";
        $this->db->execute(
            [
                ':date_before' => $ageDays,
                ':field' => $field,
                ':item_type' => $type,
            ]
        );
    }
}
