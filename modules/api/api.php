<?php

class Page extends PageCommon {

    public $files_ver = 11;

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        global $smarty;
        parent::__construct($db, 'api', $page_id);
        $id = urldecode($id);
        if (strpos($id, '?') !== FALSE)
            $id = substr($id, 0, strpos($id, '?'));
        $this->id = $id;
        $this->auth->setService('api');
        /*
          include _DIR_INCLUDES . "/class.Identify.php";
          $ident = new Identify('api');
          $ident->check();
         */
        //========================  I N D E X  ================================
        if ($page_id == '0') {//карта
            $this->content = $this->getApi0($smarty);
            return true;
        } elseif ($page_id == '1' && isset($_GET['center'])) {//список
            $this->content = $this->getApi1($smarty);
            return true;
        } elseif ($page_id == '2' && isset($_GET['id'])) {//место
            $this->content = $this->getApi2($smarty, intval($_GET['id']));
            return true;
        } elseif ($page_id == '') {
            header("Location: /api/0/");
        }
        //==========================  E X I T  ================================
        else
            $this->getError('404');
    }

    private function getApi0($smarty) {
        return $smarty->fetch(_DIR_TEMPLATES . '/api/map0.sm.html');
    }

    private function getApi1($smarty) {
        $db = $this->db;
        $dbpt = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dpru = $db->getTableName('region_url');
        $dprt = $db->getTableName('ref_pointtypes');

        list($c_lat, $c_lon) = explode(',', cut_trash_string($_GET['center']));
        $c_lat = cut_trash_float($c_lat);
        $c_lon = cut_trash_float($c_lon);

        if (isset($_GET['filter'])) {
            if ($_GET['filter'] == "sights")
                $filter = "AND rt.tr_sight = 1\n";
            if ($_GET['filter'] == "useful")
                $filter = "AND rt.tr_sight = 0\n";
        } else {
            $filter = '';
        }

        $db->sql = "SELECT pt.*, rt.tp_name, rt.tp_icon,
                           ru.url,
                           ROUND(6371 * 1000 * acos(sin(RADIANS(pt.pt_latitude)) * sin(RADIANS($c_lat)) + cos(RADIANS(pt.pt_latitude)) * cos(RADIANS($c_lat)) * cos(RADIANS(pt.pt_longitude) - RADIANS($c_lon)))) AS dist_m
                    FROM $dbpt pt
                    LEFT JOIN $dprt rt ON rt.tp_id = pt.pt_type_id
                    LEFT JOIN $dbpc pc ON pc.pc_id = pt.pt_citypage_id
                    LEFT JOIN $dpru ru ON ru.uid = pc.pc_url_id
                    WHERE pt.pt_active = 1
                    $filter
                    AND pt.pt_latitude > 0 AND pt.pt_longitude > 0
                    ORDER BY dist_m
                    LIMIT 20";
        //$db->showSQL();
        $db->exec();
        $points = array();
        while ($pt = $db->fetch()) {
            $pt['pt_description'] = strip_tags($pt['pt_description']);
            $pt['pt_description'] = html_entity_decode($pt['pt_description'], ENT_QUOTES, 'UTF-8');
            $short_end = @mb_strpos($pt['pt_description'], ' ', 350, 'utf-8');
            $pt['pt_short'] = trim(mb_substr($pt['pt_description'], 0, $short_end, 'utf-8'), "\x00..\x1F,.-");
            $pt['pt_dist'] = $this->calcGeodesicLine($c_lat, $c_lon, $pt['pt_latitude'], $pt['pt_longitude']);
            $points[] = $pt;
        }

        $geocode_url = "http://geocode-maps.yandex.ru/1.x/?geocode=N$c_lat,+E$c_lon&lang=ru-RU&format=json&key=";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $geocode_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $answer = curl_exec($ch);
        curl_close($ch);
        $json_response = json_decode($answer, true);

        $smarty->assign('points', $points);
        $smarty->assign('geocoder_info', $json_response['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['metaDataProperty']['GeocoderMetaData']['text']);
        return $smarty->fetch(_DIR_TEMPLATES . '/api/api1.sm.html');
    }

    private function getApi2($smarty, $id) {
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
                    WHERE pt.pt_active = 1
                    AND pt.pt_id = '$id'
                    LIMIT 1";
        $db->exec();
        $pt = $db->fetch();
        $smarty->assign('object', $pt);
        return $smarty->fetch(_DIR_TEMPLATES . '/api/api2.sm.html');
    }

    private function calcGeodesicLine($lat1, $lon1, $lat2, $lon2) {
        return round(6371 * 1000 * acos(sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon1) - deg2rad($lon2))));
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}

?>
