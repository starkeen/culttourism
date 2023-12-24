<?php

declare(strict_types=1);

namespace app\modules;

use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\exceptions\NotFoundException;

class ApiModule extends Module implements ModuleInterface
{
    /**
     * @inheritDoc
     * @throws     NotFoundException
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        $pageId = (string) $request->getLevel1();

        $this->webUser->getAuth()->setService('api');

        //========================  I N D E X  ================================
        if ($pageId === '0') {//карта
            $response->getContent()->setBody($this->getApi0());
        } elseif ($pageId === '1' && isset($_GET['center'])) {//список
            $response->getContent()->setBody($this->getApi1());
        } elseif ($pageId === '2' && isset($_GET['id'])) {//место
            $response->getContent()->setBody($this->getApi2((int) $_GET['id']));
        } elseif ($pageId === '3' && isset($_GET['center'])) {//адрес
            $response->getContent()->setBody($this->getApi3($_GET['center']));
        } elseif ($pageId === '4' && isset($_GET['center'])) {//список xml
            $this->getApi4();
        } elseif ($pageId === '5' && isset($_GET['id'])) {//место xml
            $this->getApi5((int) $_GET['id']);
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'api';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }

    /**
     * @return false|string
     */
    private function getApi0()
    {
        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/api/map0.tpl');
    }

    /**
     * @return false|string
     */
    private function getApi1()
    {
        $dbpt =  $this->db->getTableName('pagepoints');
        $dbpc =  $this->db->getTableName('pagecity');
        $dpru =  $this->db->getTableName('region_url');
        $dprt =  $this->db->getTableName('ref_pointtypes');

        [$c_lat, $c_lon] = explode(',', trim($_GET['center']));
        $c_lat = cut_trash_float($c_lat);
        $c_lon = cut_trash_float($c_lon);

        if (isset($_GET['filter'])) {
            if ($_GET['filter'] === "sights") {
                $filter = "AND rt.tr_sight = 1\n";
            }
            if ($_GET['filter'] === "useful") {
                $filter = "AND rt.tr_sight = 0\n";
            }
        } else {
            $filter = '';
        }

        $this->db->sql = "SELECT pt.*, rt.tp_name, rt.tp_icon,
                           ru.url,
                           ROUND(6371 * 1000 * acos(sin(RADIANS(pt.pt_latitude)) * sin(RADIANS($c_lat)) + cos(RADIANS(pt.pt_latitude)) * cos(RADIANS($c_lat)) * cos(RADIANS(pt.pt_longitude) - RADIANS($c_lon)))) AS dist_m
                    FROM $dbpt pt
                    LEFT JOIN $dprt rt ON rt.tp_id = pt.pt_type_id
                    LEFT JOIN $dbpc pc ON pc.pc_id = pt.pt_citypage_id
                    LEFT JOIN $dpru ru ON ru.uid = pc.pc_url_id
                    WHERE pt.pt_deleted_at IS NULL
                    $filter
                    AND pt.pt_latitude > 0 AND pt.pt_longitude > 0
                    ORDER BY dist_m
                    LIMIT 20";
        $this->db->exec();
        $points = [];
        while ($pt =  $this->db->fetch()) {
            $pt['pt_description'] = strip_tags($pt['pt_description']);
            $pt['pt_description'] = html_entity_decode($pt['pt_description'], ENT_QUOTES, 'UTF-8');
            $short_end = @mb_strpos($pt['pt_description'], ' ', 350, 'utf-8');
            $pt['pt_short'] = trim(mb_substr($pt['pt_description'], 0, $short_end, 'utf-8'), "\x00..\x1F,.-");
            $pt['pt_dist'] = $this->calcGeodesicLine($c_lat, $c_lon, $pt['pt_latitude'], $pt['pt_longitude']);
            $points[] = $pt;
        }

        $this->templateEngine->assign('points', $points);

        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/api/api1.tpl');
    }

    /**
     * @param  $id
     * @return false|string
     */
    private function getApi2($id)
    {
        $db = $this->db;
        $dbpt = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dpru = $db->getTableName('region_url');
        $dprt = $db->getTableName('ref_pointtypes');
        $db->sql = "SELECT pt.*, rt.tp_name, rt.tp_icon,
                           ru.url
                    FROM $dbpt pt
                    LEFT JOIN $dprt rt ON rt.tp_id = pt.pt_type_id
                    LEFT JOIN $dbpc pc ON pc.pc_id = pt.pt_citypage_id
                    LEFT JOIN $dpru ru ON ru.uid = pc.pc_url_id
                    WHERE pt.pt_deleted_at IS NULL
                    AND pt.pt_id = '$id'
                    LIMIT 1";
        $db->exec();
        $pt = $db->fetch();
        $this->templateEngine->assign('object', $pt);

        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/api/api2.tpl');
    }

    /**
     * @param  $center
     * @return mixed
     */
    private function getApi3($center)
    {
        [$c_lat, $c_lon] = explode(',', trim($center));
        $c_lat = cut_trash_float($c_lat);
        $c_lon = cut_trash_float($c_lon);

        $geocode_url = "http://geocode-maps.yandex.ru/1.x/?geocode=N$c_lat,+E$c_lon&lang=ru-RU&format=json&key=";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $geocode_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $answer = curl_exec($ch);
        curl_close($ch);
        $json_response = json_decode($answer, true);

        return $json_response['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['metaDataProperty']['GeocoderMetaData']['text'];
    }

    /**
     */
    private function getApi4()
    {
        $db = $this->db;
        $dbpt = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dpru = $db->getTableName('region_url');
        $dprt = $db->getTableName('ref_pointtypes');

        [$c_lat, $c_lon] = explode(',', trim($_GET['center']));
        $c_lat = cut_trash_float($c_lat);
        $c_lon = cut_trash_float($c_lon);
        $points = [];
        if ($c_lat != 0 && $c_lon != 0) {
            if (isset($_GET['filter'])) {
                if ($_GET['filter'] === "sights") {
                    $filter = "AND rt.tr_sight = 1\n";
                }
                if ($_GET['filter'] === "useful") {
                    $filter = "AND rt.tr_sight = 0\n";
                }
            } else {
                $filter = '';
            }

            $db->sql = "SELECT pt.*, rt.tp_name, rt.tp_icon, rt.tr_sight,
                           ru.url,
                           ROUND(6371 * 1000 * acos(sin(RADIANS(pt.pt_latitude)) * sin(RADIANS($c_lat)) + cos(RADIANS(pt.pt_latitude)) * cos(RADIANS($c_lat)) * cos(RADIANS(pt.pt_longitude) - RADIANS($c_lon)))) AS dist_m
                    FROM $dbpt pt
                    LEFT JOIN $dprt rt ON rt.tp_id = pt.pt_type_id
                    LEFT JOIN $dbpc pc ON pc.pc_id = pt.pt_citypage_id
                    LEFT JOIN $dpru ru ON ru.uid = pc.pc_url_id
                    WHERE pt.pt_deleted_at IS NULL
                    $filter
                    AND pt.pt_latitude > 0 AND pt.pt_longitude > 0
                    ORDER BY dist_m
                    LIMIT 20";
            $db->exec();
            $remove_symbols = ["\n", "\t"];

            while ($pt = $db->fetch()) {
                $pt['pt_description'] = strip_tags($pt['pt_description']);
                $pt['pt_description'] = html_entity_decode($pt['pt_description'], ENT_QUOTES, 'UTF-8');
                $short_end = @mb_strpos($pt['pt_description'], ' ', 350, 'utf-8');
                $pt['pt_short'] = trim(
                    str_replace(
                        $remove_symbols,
                        '',
                        trim(mb_substr($pt['pt_description'], 0, $short_end, 'utf-8'), "\x00..\x1F,.-")
                    )
                );
                $pt['pt_dist'] = $this->calcGeodesicLine($c_lat, $c_lon, $pt['pt_latitude'], $pt['pt_longitude']);
                $pt['pt_adress'] = trim(str_replace($remove_symbols, '', $pt['pt_adress']));
                $pt['tp_icon'] = 'o_' . str_replace('.png', '', $pt['tp_icon']);
                if (!$pt['pt_adress']) {
                    $pt['pt_adress'] = ' ';
                }
                if (!$pt['pt_short']) {
                    $pt['pt_short'] = ' ';
                }
                $points[] = $pt;
            }
        }
        $this->templateEngine->assign('current', $this->getApi3("$c_lat,$c_lon"));
        $this->templateEngine->assign('points', $points);
        header("Content-type: application/xml");
        echo $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/api/api4.sm.xml');
        exit();
    }

    /**
     * @param $id
     */
    private function getApi5($id)
    {
        $db = $this->db;
        $dbpt = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dpru = $db->getTableName('region_url');
        $dprt = $db->getTableName('ref_pointtypes');
        $db->sql = "SELECT pt.*, rt.tp_name, rt.tp_icon,
                           ru.url
                    FROM $dbpt pt
                    LEFT JOIN $dprt rt ON rt.tp_id = pt.pt_type_id
                    LEFT JOIN $dbpc pc ON pc.pc_id = pt.pt_citypage_id
                    LEFT JOIN $dpru ru ON ru.uid = pc.pc_url_id
                    WHERE pt.pt_deleted_at IS NULL
                    AND pt.pt_id = '$id'
                    LIMIT 1";
        $db->exec();
        $pt = $db->fetch();
        $line_breaks = ["<br/>", "<br>"];
        $pt['pt_description'] = trim(
            strip_tags(html_entity_decode(str_replace($line_breaks, "\n", $pt['pt_description']), ENT_QUOTES, "UTF-8"))
        );
        $this->templateEngine->assign('point', $pt);
        header("Content-type: application/xml");
        echo $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/api/api5.sm.xml');
        exit();
    }

    /**
     * @param  float $lat1
     * @param  float $lon1
     * @param  float $lat2
     * @param  float $lon2
     * @return float
     */
    private function calcGeodesicLine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return round(
            6371 * 1000 * acos(
                sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(
                    deg2rad($lon1) - deg2rad($lon2)
                )
            )
        );
    }
}
