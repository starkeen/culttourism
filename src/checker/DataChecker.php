<?php

declare(strict_types=1);

namespace app\checker;

use app\component\typograph\Typograph;
use app\db\MyDB;
use app\utils\GPS;
use Curl;
use Dadata\DadataClient;
use MBlogEntries;
use MCandidatePoints;
use MDataCheck;
use models\MPhones;
use MPageCities;
use MPagePoints;

class DataChecker
{
    private MyDB $db;

    private Typograph $typograph;

    private DadataClient $dadata;

    private string $entityType = 'type';
    private string $entityId = 'id';
    private string $entityField;

    /**
     * @var string[]
     */
    private array $dotting = [
        ' г ' => ' г. ',
        ' пос ' => ' пос. ',
        ' ул ' => ' ул. ',
        ' пл ' => ' пл. ',
        ' ш ' => ' ш. ',
        ' им ' => ' им. ',
        ' д ' => ' д. ',
    ];

    /**
     * @param MyDB $db
     * @param Typograph $typograph
     */
    public function __construct(MyDB $db, Typograph $typograph, DadataClient $dadata)
    {
        $this->db = $db;
        $this->typograph = $typograph;
        $this->dadata = $dadata;
    }

    /**
     * @param int $count
     *
     * @return array[]
     */
    public function repairPointsAddresses(int $count = 100): array
    {
        $this->entityType = MDataCheck::ENTITY_POINTS;
        $this->entityId = 'pt_id';
        $this->entityField = 'pt_adress';
        $p = new MPagePoints($this->db);
        $dc = new MDataCheck($this->db);

        $curl = new Curl($this->db);
        $curl->setTTLDays(7);
        $curl->config(CURLOPT_TIMEOUT, 5);
        $curl->config(CURLOPT_HEADER, 0);
        $curl->config(CURLOPT_SSL_VERIFYPEER, false);

        $points = $p->getPointsWithoutAddrs($count);
        $log = [];
        foreach ($points as $pt) {
            $request = 'https://geocode-maps.yandex.ru/1.x/?format=json'
                . '&geocode=' . $pt['pt_longitude'] . ',' . $pt['pt_latitude']
                . '&ll=37.618920,55.756994'
                . '&kind=house&results=1';
            $answer = $curl->get($request);
            if ($answer === null) {
                break;
            }
            $data = json_decode($answer);
            if (empty($data->response->GeoObjectCollection)) {
                break;
            }
            $foundedFlag = $data->response->GeoObjectCollection->metaDataProperty->GeocoderResponseMetaData->found ?? 0;
            $founded = $foundedFlag > 0;
            $featureMember = $data->response->GeoObjectCollection->featureMember;
            $addrVariant = [
                'text' => $pt['pt_adress'],
                'delta_meters' => 100500,
            ];
            foreach ($featureMember as $fm) {
                $posLatLon = explode(' ', $fm->GeoObject->Point->pos);
                $addrVariant = [
                    'text' => isset($fm->GeoObject->metaDataProperty->GeocoderMetaData)
                        ? $fm->GeoObject->metaDataProperty->GeocoderMetaData->text
                        : $fm->GeoObject->name,
                    'gps' => [
                        'latitude' => $posLatLon[1],
                        'longitude' => $posLatLon[0],
                    ],
                    'delta_lat' => round(abs($pt['pt_latitude'] - $posLatLon[1]), 5),
                    'delta_lon' => round(abs($pt['pt_longitude'] - $posLatLon[0]), 5),
                    'delta_meters' => GPS::distanceGPS(
                        (float) $pt['pt_latitude'],
                        (float) $pt['pt_longitude'],
                        (float) $posLatLon[1],
                        (float) $posLatLon[0]
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
                $dc->markChecked($this->entityType, $pt[$this->entityId], $this->entityField, $addrVariant['text']);
            } else {
                $dc->markChecked(
                    $this->entityType,
                    $pt['pt_id'],
                    $this->entityField,
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
     */
    public function repairPointsPhones(int $count = 10): array
    {
        $log = [];

        $this->entityType = MDataCheck::ENTITY_POINTS;
        $this->entityId = 'pc_id';
        $this->entityField = 'pt_phone';

        $dataChecker = new MDataCheck($this->db);
        $ptModel = new MPagePoints($this->db);
        $phonesModel = new MPhones($this->db);

        $points = $ptModel->getPointsWithPhones($count);
        foreach ($points as $pt) {
            $logItem = [];
            $pointId = (int) $pt['pt_id'];
            $phoneString = $pt['pt_phone'];
            $phoneItems = explode(',', $phoneString);

            $phonesModel->deleteByPoint($pointId);
            foreach ($phoneItems as $phoneItem) {
                $phoneItem = trim($phoneItem);
                $phonesModel->insert(
                    [
                        'phone_raw' => $phoneItem,
                        'id_point' => $pointId,
                        'id_city' => (int) $pt['pc_id'],
                    ]
                );
            }
            $phonesModel->process();

            // исправление формата телефонов
            $newPhones = trim($phoneString);
            if ($newPhones !== $phoneString) {
                $ptModel->updateByPk(
                    $pointId,
                    [
                        'pt_phone' => $newPhones,
                    ]
                );
                $logItem = [
                    'pt_id' => $pointId,
                    'old_phone' => $phoneString,
                    'new_phone' => $newPhones,
                ];
            }

            $dataChecker->markChecked(
                $this->entityType,
                $pt['pt_id'],
                $this->entityField,
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
     */
    public function repairPointsCoordinates(int $count = 10): array
    {
        $log = [];

        $this->entityType = MDataCheck::ENTITY_POINTS;
        $this->entityId = 'pt_id';
        $this->entityField = 'pt_latitude';
        $p = new MPagePoints($this->db);
        $dc = new MDataCheck($this->db);

        if ($this->dadata->getBalance() < 5.0) {
            echo 'Баланс Dadata.ru нулевой';

            return [];
        }

        $curl = new Curl($this->db);
        $curl->setTTLDays(7);
        $curl->config(CURLOPT_TIMEOUT, 5);
        $curl->config(CURLOPT_HEADER, 0);
        $curl->config(CURLOPT_SSL_VERIFYPEER, false);

        $points = $p->getPointsWithoutCoordinates($count);
        foreach ($points as $pt) {
            $addr = preg_replace('/(\d{3})(\s)(\d{3})/', '$1$3', $pt['pt_adress']);
            $result = $this->dadata->clean('address', $addr);
            $coordinates = '';
            if (
                (int) $result['qc'] === 0
                && (int) $result['qc_geo'] === 0
                && (float) $result['geo_lat'] !== 0.0
                && (float) $result['geo_lon'] !== 0.0
            ) {
                $coordinates = sprintf('%f, %f', $result['geo_lat'], $result['geo_lon']);
                $geoData = [
                    'pt_latitude' => (float) $result['geo_lat'],
                    'pt_longitude' => (float) $result['geo_lon'],
                ];
                $p->updateByPk($pt[$this->entityId], $geoData);
                $log[] = [
                    $pt['pt_id'],
                    $pt['pt_name'],
                    $pt['pt_adress'],
                    $result['result'],
                    $coordinates,
                ];
            }

            $dc->markChecked($this->entityType, $pt[$this->entityId], $this->entityField, $coordinates);
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairCandidates(int $count = 10): array
    {
        $this->entityType = MDataCheck::ENTITY_CANDIDATES;
        $this->entityId = 'cp_id';
        $dc = new MDataCheck($this->db);
        $cp = new MCandidatePoints($this->db);
        $log = [];

        $fields = ['cp_text', 'cp_title', 'cp_phone'];
        foreach ($fields as $fld) {
            $this->entityField = $fld;
            $items = $this->getCheckingPortion($count, 'cp_active');
            foreach ($items as $item) {
                $result = $this->typograph->typo($item[$this->entityField]);
                $cp->updateByPk($item[$this->entityId], [$this->entityField => $result]);
                $dc->markChecked($this->entityType, $item[$this->entityId], $this->entityField, $result);
            }
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairCandidatesAddresses(int $count = 10): array
    {
        $log = [];
        $this->entityType = MDataCheck::ENTITY_CANDIDATES;
        $this->entityId = 'cp_id';
        $this->entityField = 'cp_addr';
        $dc = new MDataCheck($this->db);
        $cp = new MCandidatePoints($this->db);

        if ($this->dadata->getBalance() < 5.0) {
            echo 'Баланс Dadata.ru нулевой';

            return [];
        }

        $items = $this->getCheckingPortion($count, 'cp_active', true);
        foreach ($items as $item) {
            $addr = preg_replace('/(\d{3})(\s)(\d{3})/', '$1$3', $item[$this->entityField]);
            $cleaned = $this->dadata->clean('address', $addr);
            $result = $cleaned['result'];
            $quality = $cleaned['qc'] ?? null;
            if ($quality === 0) {
                $dotted = str_replace(array_keys($this->dotting), array_values($this->dotting), $cleaned['result']);
                $result = $this->typograph->typo($dotted);
                $cp->updateByPk($item[$this->entityId], [$this->entityField => $result]);

                if (
                    (int) $item['cp_latitude'] === 0
                    && (int) $item['cp_longitude'] === 0
                    && (int) $cleaned['qc_geo'] === 0
                ) {
                    $geoData = [
                        'cp_latitude' => $cleaned['geo_lat'],
                        'cp_longitude' => $cleaned['geo_lon'],
                    ];
                    $cp->updateByPk($item[$this->entityId], $geoData);
                }
            } else {
                $result = '[quality:' . $quality . '] ' . $result;
                $log[] = [
                    'quality_parse',
                    $addr,
                    $cleaned,
                ];
            }
            $dc->markChecked($this->entityType, $item[$this->entityId], $this->entityField, $result);
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairBlog(int $count = 10): array
    {
        $log = [];
        $this->entityType = MDataCheck::ENTITY_BLOG;
        $this->entityId = 'br_id';
        $dc = new MDataCheck($this->db);
        $be = new MBlogEntries($this->db);

        $this->entityField = 'br_text';
        $items = $this->getCheckingPortion($count, 'br_id');
        foreach ($items as $item) {
            $result = $this->typograph->typo($item[$this->entityField]);
            $be->updateByPk($item[$this->entityId], [$this->entityField => $result]);
            $dc->markChecked($this->entityType, $item[$this->entityId], $this->entityField, $result);
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairPoints(int $count = 10): array
    {
        $log = [];
        $this->entityType = MDataCheck::ENTITY_POINTS;
        $this->entityId = 'pt_id';
        $dc = new MDataCheck($this->db);
        $pt = new MPagePoints($this->db);
        $pt->setSkipUpdateDate(true);

        $fields = ['pt_name', 'pt_description', 'pt_adress'];
        foreach ($fields as $fld) {
            $this->entityField = $fld;
            $items = $this->getCheckingPortion($count, 'pt_active');
            foreach ($items as $item) {
                $result = $this->typograph->typo($item[$this->entityField]);
                if ($result !== $item[$this->entityField]) {
                    $pt->updateByPk($item[$this->entityId], [$this->entityField => $result]);
                }
                $dc->markChecked($this->entityType, $item[$this->entityId], $this->entityField, $result);
            }
        }

        return $log;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function repairCity(int $count = 10): array
    {
        $log = [];
        $this->entityType = MDataCheck::ENTITY_CITIES;
        $this->entityId = 'pc_id';
        $dc = new MDataCheck($this->db);
        $pc = new MPageCities($this->db);
        $pc->setSkipUpdateDate(true);

        $fields = ['pc_text', 'pc_announcement', 'pc_description'];
        foreach ($fields as $fld) {
            $this->entityField = $fld;
            $items = $this->getCheckingPortion($count, 'pc_active');
            foreach ($items as $item) {
                $result = $this->typograph->typo($item[$this->entityField]);
                if ($result !== $item[$this->entityField]) {
                    $pc->updateByPk($item[$this->entityId], [$this->entityField => $result]);
                }
                $dc->markChecked($this->entityType, $item[$this->entityId], $this->entityField, $result);
            }
        }

        return $log;
    }

    /**
     * @param int $limit
     * @param string $activeField
     * @param bool $unchecked
     *
     * @return array
     */
    private function getCheckingPortion(int $limit, string $activeField, bool $unchecked = false): array
    {
        $checkerTable = $this->db->getTableName('data_check');
        $tableName = $this->db->getTableName($this->entityType);
        $this->db->sql = "SELECT t.*
                            FROM $tableName t
                                LEFT JOIN $checkerTable dc ON dc.dc_item_id = t.$this->entityId
                                    AND dc.dc_type = :item_type
                                    AND dc.dc_field = :field
                            WHERE t.$activeField > 0
                                AND t.$this->entityField != ''\n";
        if ($unchecked) {
            $this->db->sql .= " AND dc.dc_id IS NULL\n";
        }
        $this->db->sql .= " GROUP BY t.$this->entityId
                            ORDER BY dc.dc_date
                            LIMIT :limit";
        $this->db->execute(
            [
                ':limit' => $limit,
                ':field' => $this->entityField,
                ':item_type' => $this->entityType,
            ]
        );

        return $this->db->fetchAll();
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
