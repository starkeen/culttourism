<?php

class Page extends PageCommon {

    public function __construct($module_id, $page_id) {
        global $db;
        global $smarty;
        parent::__construct($db, 'city', $page_id);

        if ($page_id[1] == '') {
            return $this->pageCity($db, $smarty);
        } elseif ($page_id[1] == 'add') {
            return $this->addCity($db, $smarty);
        } elseif ($page_id[1] == 'detail') {
            return $this->detailCity($db, $smarty);
        } elseif ($page_id[1] == 'meta') {
            return $this->metaCity($db, $smarty);
        } elseif ($page_id[1] == 'weather') {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 1, 2050);
            $this->isAjax = true;
            return $this->getBlockWeather($_GET['lat'], $_GET['lon']);
        } else {
            return $this->getError('404');
        }
    }

    private function getBlockWeather($lat, $lon) {
        $lat = cut_trash_float($lat);
        $lon = cut_trash_float($lon);

        $out = array('state' => false, 'content' => '', 'color' => '');
        $weather_data = array(
            'temperature' => '',
            'temperature_min' => '',
            'temperature_max' => '',
            'temp_range' => '',
            'pressure' => 0,
            'humidity' => 0,
            'windspeed' => 0,
            'winddirect' => '',
            'winddeg' => 0,
            'clouds' => 0,
            'weather_id' => 800,
            'weather_icon' => '01d',
            'weather_text' => '',
            'weather_descr' => '',
            'weather_full' => '',
        );

        $url = "http://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result);
        if (is_object($response) && $response->cod == 200) {
            $weather_data['temperature'] = round($response->main->temp - 273.15);
            if ($weather_data['temperature'] > 0) {
                $weather_data['temperature'] = '+' . $weather_data['temperature'];
            }
            if (isset($response->main->temp_min) && isset($response->main->temp_max)) {
                if (round($response->main->temp_min) != round($response->main->temp_max)) {
                    $weather_data['temperature_min'] = round($response->main->temp_min - 273.15);
                    $weather_data['temperature_max'] = round($response->main->temp_max - 273.15);
                    if ($weather_data['temperature_min'] > 0) {
                        $weather_data['temperature_min'] = '+' . $weather_data['temperature_min'];
                    }
                    if ($weather_data['temperature_max'] > 0) {
                        $weather_data['temperature_max'] = '+' . $weather_data['temperature_max'];
                    }
                    $weather_data['temp_range'] = $weather_data['temperature_min'] . '&hellip;' . $weather_data['temperature_max'];
                }
            }
            $weather_data['pressure'] = round($response->main->pressure / 10);
            $weather_data['humidity'] = round($response->main->humidity);
            $weather_data['windspeed'] = round($response->wind->speed, 1);
            $weather_data['winddeg'] = $response->wind->deg;
            $weather_data['clouds'] = $response->clouds->all;
            if (isset($response->weather[0])) {
                $weather_data['weather_id'] = $response->weather[0]->id;
                $weather_data['weather_text'] = $response->weather[0]->main;
                $weather_data['weather_descr'] = $response->weather[0]->description;
                $weather_data['weather_icon'] = $response->weather[0]->icon;
                $cond = $this->getWeaterConditionsByCode($weather_data['weather_id']);
                if ($cond) {
                    $weather_data['weather_text'] = $cond['main'];
                    $weather_data['weather_descr'] = $cond['description'];
                }
                $weather_data['weather_full'] = $weather_data['weather_text'];
                if ($weather_data['weather_descr']) {
                    $weather_data['weather_full'] .= ', ' . $weather_data['weather_descr'];
                }
            }
            if ($weather_data['winddeg'] >= 0 && $weather_data['winddeg'] <= 22.5) {
                $weather_data['winddirect'] = 'сев';
            } elseif ($weather_data['winddeg'] >= 22.5 && $weather_data['winddeg'] <= 67.5) {
                $weather_data['winddirect'] = 'с-в';
            } elseif ($weather_data['winddeg'] >= 67.5 && $weather_data['winddeg'] <= 112.5) {
                $weather_data['winddirect'] = 'вост';
            } elseif ($weather_data['winddeg'] >= 112.5 && $weather_data['winddeg'] <= 157.5) {
                $weather_data['winddirect'] = 'ю-в';
            } elseif ($weather_data['winddeg'] >= 157.5 && $weather_data['winddeg'] <= 202.5) {
                $weather_data['winddirect'] = 'юж';
            } elseif ($weather_data['winddeg'] >= 202.5 && $weather_data['winddeg'] <= 247.5) {
                $weather_data['winddirect'] = 'ю-3';
            } elseif ($weather_data['winddeg'] >= 247.5 && $weather_data['winddeg'] <= 292.5) {
                $weather_data['winddirect'] = 'зап';
            } elseif ($weather_data['winddeg'] >= 292.5 && $weather_data['winddeg'] <= 67.5) {
                $weather_data['winddirect'] = 'с-з';
            } else {
                $weather_data['winddirect'] = 'сев';
            }
            $this->smarty->assign('weather_data', $weather_data);
            $out['state'] = true;
            $out['content'] = $this->smarty->fetch(_DIR_TEMPLATES . '/city/weather.block.sm.html');
        }
        header("Content-type: application/json");
        echo json_encode($out);
        exit();
    }

    private function getWeaterConditionsByCode($code) {
        $code = cut_trash_int($code);
        $db = $this->db;
        $dbwc = $db->getTableName('weather_codes');
        $db->sql = "SELECT * FROM $dbwc WHERE wc_id = '$code'";
        $db->exec();
        $row = $db->fetch();
        if ($row['wc_id'] != 0) {
            return array('main' => $row['wc_main'], 'description' => $row['wc_description']);
        } else {
            return false;
        }
    }

    private function metaCity($db, $smarty) {
        $dbcd = $db->getTableName('city_data');
        $dbcf = $db->getTableName('city_fields');
        $dbpc = $db->getTableName('pagecity');

        if (isset($_POST['act'])) {
            if (!$this->checkEdit()) {
                return $this->getError('403');
            }
            $uid = $this->getUserId();
            switch ($_POST['act']) {
                case 'add':
                    $cf_id = cut_trash_int($_POST['cf']);
                    $value = cut_trash_text($_POST['val']);
                    $city_id = cut_trash_int($_POST['cpid']);
                    $db->sql = "DELETE FROM $dbcd WHERE cd_pc_id = '$city_id' AND cd_cf_id = '$cf_id'";
                    $db->exec();
                    $db->sql = "INSERT INTO $dbcd SET cd_pc_id = '$city_id', cd_cf_id = '$cf_id', cd_value = '$value'";
                    if ($value != '') {
                        $db->exec();
                    }
                    $db->sql = "SELECT * FROM  $dbcf WHERE cf_id = '$cf_id'";
                    $db->exec();
                    $row = $db->fetch();
                    $db->sql = "UPDATE $dbpc SET pc_lastup_date = now(), pc_lastup_user = '$uid' WHERE pc_id = '$city_id'";
                    $db->exec();
                    echo $row['cf_title'];
                    break;
                case 'del':
                    $cf_id = cut_trash_int($_POST['cf']);
                    $city_id = cut_trash_int($_POST['cpid']);
                    $db->sql = "DELETE FROM $dbcd WHERE cd_pc_id = '$city_id' AND cd_cf_id = '$cf_id'";
                    $db->exec();
                    $db->sql = "UPDATE $dbpc SET pc_lastup_date = now(), pc_lastup_user = '$uid' WHERE pc_id = '$city_id'";
                    $db->exec();
                    echo 'ok';
                    break;
                case 'edit':
                    $cf_id = cut_trash_int($_POST['cf']);
                    $city_id = cut_trash_int($_POST['cpid']);
                    $value = cut_trash_text($_POST['val']);
                    $db->sql = "UPDATE $dbcd SET cd_value = '$value' WHERE cd_pc_id = '$city_id' AND cd_cf_id = '$cf_id'";
                    if ($value != '') {
                        $db->exec();
                    }
                    $db->sql = "UPDATE $dbpc SET pc_lastup_date = now(), pc_lastup_user = '$uid' WHERE pc_id = '$city_id'";
                    $db->exec();
                    echo 'ok';
                    break;
            }
        } else {
            $id = cut_trash_int($_GET['id']);
            $db->sql = "SELECT cf_title, cd_value
                        FROM $dbcd cd
                            LEFT JOIN $dbcf cf ON cf.cf_id = cd.cd_cf_id
                        WHERE cd.cd_pc_id = '$id'
                            AND cd.cd_value != ''
                            AND cf.cf_active = 1
                        ORDER BY cf_order";
            $db->exec();
            $metas = array();
            while ($row = $db->fetch()) {
                $metas[] = $row;
            }
            $smarty->assign('metas', $metas);
            header('Content-Type: text/html; charset=utf-8');
            $smarty->display(_DIR_TEMPLATES . '/city/meta.sm.html');
        }
        exit();
    }

    private function detailCity($db, $smarty) {
        //**************************************** РЕДАКТИРОВАНИЕ ******************
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        if (!isset($_GET['city_id'])) {
            return $this->getError('404');
        }
        $city_id = cut_trash_int($_GET['city_id']);
        if (!$city_id) {
            return $this->getError('404');
        }

        $uid = $this->getUserId();
        $dbc = $db->getTableName('pagecity');
        $dbu = $db->getTableName('region_url');
        $dbcd = $db->getTableName('city_data');
        $dbcf = $db->getTableName('city_fields');
        $dbws = $db->getTableName('wordstat');

        if (isset($_POST) && !empty($_POST)) {
            //print_x($_POST);
            $pc_keywords = cut_trash_string($_POST['keywds']);
            $pc_description = cut_trash_text($_POST['descr']);
            $pc_latitude = cut_trash_string($_POST['latitude']);
            $pc_latitude = str_replace(',', '.', $pc_latitude);
            $pc_longitude = cut_trash_string($_POST['longitude']);
            $pc_longitude = str_replace(',', '.', $pc_longitude);
            $pc_osm_id = intval($_POST['osm_id']);
            $pc_inwheretext = cut_trash_string($_POST['inwhere']);
            $pc_title_english = cut_trash_string($_POST['title_eng']);
            $pc_title_translit = cut_trash_string($_POST['translit']);
            $pc_title_synonym = cut_trash_string($_POST['synonym']);
            $pc_website = cut_trash_string($_POST['web']);
            $pc_announcement = cut_trash_text($_POST['anons']);
            $url = cut_trash_string($_POST['url']);
            $db->sql = "UPDATE $dbc SET
                        pc_keywords = '$pc_keywords', pc_description = '$pc_description',
                        pc_announcement = '$pc_announcement',
                        pc_latitude = '$pc_latitude', pc_longitude = '$pc_longitude',
                        pc_osm_id = '$pc_osm_id',
                        pc_inwheretext = '$pc_inwheretext', pc_title_synonym = '$pc_title_synonym',
                        pc_title_english = '$pc_title_english', pc_title_translit = '$pc_title_translit',
                        pc_website = '$pc_website',
                        pc_lastup_date = now(), pc_lastup_user = '$uid'
                        WHERE pc_id = '$city_id'";
            $db->exec();
            $aurl = explode('/', $url);
            $nurl = '';
            foreach ($aurl as $u) {
                if ($u != '') {
                    $nurl .= '/' . strtolower(str_replace(' ', '_', translit($u)));
                }
            }
            if ($nurl != '/') {
                $db->sql = "SELECT pc_url_id FROM $dbc WHERE pc_id = '$city_id'";
                $db->exec();
                $url = $db->fetch();
                $db->sql = "UPDATE $dbu SET url = '$nurl' WHERE uid = '{$url['pc_url_id']}'";
                $db->exec();
            }
            $db->sql = "SELECT url FROM $dbu u LEFT JOIN $dbc c ON c.pc_url_id = u.uid WHERE pc_id = '$city_id' LIMIT 1";
            $db->exec();
            $url = $db->fetch();
            header("Location: {$url['url']}");
            exit();
        }

        $db->sql = "SELECT c.pc_id, c.pc_title, c.pc_keywords, c.pc_description, c.pc_announcement,
                            c.pc_latitude, c.pc_longitude, c.pc_osm_id,
                            c.pc_inwheretext, c.pc_title_translit, c.pc_title_english,
                            c.pc_title_synonym, c.pc_website,
                            UNIX_TIMESTAMP(c.pc_lastup_date) AS last_update,
                            u.url
                    FROM $dbc c
                        LEFT JOIN $dbu u ON u.uid = c.pc_url_id
                    WHERE pc_id = '$city_id'";
        $db->exec();
        $citypage = $db->fetch();

        $db->sql = "SELECT *
                    FROM $dbcd cd
                        LEFT JOIN $dbcf cf ON cf.cf_id = cd.cd_cf_id
                    WHERE cd.cd_pc_id = '$city_id'
                    ORDER BY cf_order";
        $db->exec();
        $meta = array();
        while ($row = $db->fetch()) {
            $meta[] = $row;
        }

        $db->sql = "SELECT *
                    FROM $dbcf
                    WHERE cf_id NOT IN (SELECT cd_cf_id FROM $dbcd WHERE cd_pc_id = '$city_id')
                    ORDER BY cf_order";
        $db->exec();
        $ref_meta = array();
        while ($row = $db->fetch()) {
            $ref_meta[] = $row;
        }

        $db->sql = "SELECT *
                    FROM $dbws
                    WHERE ws_city_title = '{$citypage['pc_title']}'
                    LIMIT 1";
        $db->exec();
        $yandex = $db->fetch();

        $smarty->assign('city', $citypage);
        $smarty->assign('baseurl', $this->basepath);
        $smarty->assign('meta', $meta);
        $smarty->assign('ref_meta', $ref_meta);
        $smarty->assign('yandex', $yandex);

        $this->lastedit_timestamp = $row['last_update'];

        if (isset($this->user['userid'])) {
            $smarty->assign('adminlogined', $this->getUserId());
        }
        $this->content = $smarty->fetch(_DIR_TEMPLATES . '/city/details.sm.html');
    }

    private function addCity($db, $smarty) {
        //**************************************** ДОБАВЛЕНИЕ ******************
        $newcity = '';
        if (isset($_POST) && !empty($_POST)) {
            $city_name = cut_trash_string($_POST['city_name']);
            $city_id = cut_trash_int($_POST['city_id']);
            $region_id = cut_trash_int($_POST['region_id']);
            $country_id = cut_trash_int($_POST['country_id']);
            $country_code = cut_trash_string($_POST['country_code']);
            $lat = cut_trash_string($_POST['latitude']);
            $lat = str_replace(',', '.', $lat);
            $lon = cut_trash_string($_POST['longitude']);
            $lon = str_replace(',', '.', $lon);
            $uid = $this->getUserId();
            $dbc = $db->getTableName('pagecity');
            $dbu = $db->getTableName('region_url');
            $translit = translit($city_name);
            $db->sql = "INSERT INTO $dbc SET
                        pc_title = '$city_name', pc_city_id = '$city_id', pc_region_id = '$region_id',
                        pc_country_id = '$country_id', pc_country_code = '$country_code',
                        pc_url_id = 0, pc_latitude = '$lat', pc_longitude = '$lon', pc_rank = 0,
                        pc_title_translit = '$translit', pc_title_english = '$translit', pc_inwheretext = '$city_name',
                        pc_add_date = now(), pc_add_user = '$uid', pc_lastup_date = now()";
            if ($db->exec()) {
                $cid = $db->getLastInserted();
                $nurl = strtolower(str_replace(' ', '_', $translit));
                $db->sql = "SELECT u.url FROM $dbu u
                            LEFT JOIN $dbc c ON c.pc_url_id = u.uid
                            WHERE c.pc_region_id = '$region_id' AND c.pc_country_id = '$country_id' AND c.pc_city_id = 0
                            LIMIT 1";
                $db->exec();
                $row = $db->fetch();
                $nurl = "{$row['url']}/$nurl";
                $db->sql = "INSERT INTO $dbu SET url = '$nurl', citypage = '$cid'";
                $db->exec();
                $nuid = $db->getLastInserted();
                $db->sql = "UPDATE $dbc SET pc_url_id = '$nuid' WHERE pc_id = '$cid'";
                $db->exec();
                header("location: /city/detail/?city_id=$cid");
                exit();
            }
        } elseif (isset($_GET['cityname'])) {
            $newcity = cut_trash_string($_GET['cityname']);
            $newcity = trim($newcity);
            $newcity = mysql_real_escape_string($newcity);
            $dbc = $db->getTableName('pagecity');
            $dbu = $db->getTableName('region_url');
            $dbrc = $db->getTableName('ref_city');
            $dbrr = $db->getTableName('ref_region');
            $dbrs = $db->getTableName('ref_country');
            $dbll = $db->getTableName('ref_citylatlon');
            //------------------- поиск уже имеющихся --------------
            $db->sql = "SELECT url.url, city.pc_title
                        FROM $dbc city
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE city.pc_title LIKE '%$newcity%' OR city.pc_title_synonym LIKE '%$newcity%'";
            $db->exec();
            $already = array();
            while ($row = $db->fetch()) {
                $already[$row['url']] = $row['pc_title'];
            }
            //------------------- поиск в справочнике регионов --------------
            $db->sql = "SELECT rc.name as name, rc.id as city_id,
                            rr.name as region, rr.id as region_id,
                            rs.name as country, rs.id as country_id, rs.alpha2 AS country_code,
                            city.pc_title as pc_title, url.url
                        FROM $dbrc rc
                        LEFT JOIN $dbrr rr ON rr.id = rc.region_id
                        LEFT JOIN $dbrs rs ON rs.id = rc.country_id
                        LEFT JOIN $dbc city ON city.pc_city_id = rc.id
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rc.name LIKE '%$newcity%'
                        
                        UNION
                        
                        SELECT '' as name, 0 as city_id,
                            rr.name as region, rr.id as region_id,
                            rs.name as country, rs.id as country_id, rs.alpha2 AS country_code,
                            city.pc_title as pc_title, url.url
                        FROM $dbrr rr
                        LEFT JOIN $dbrs rs ON rs.id = rr.country_id
                        LEFT JOIN $dbc city ON city.pc_region_id = rr.id AND city.pc_city_id = 0
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rr.name LIKE '%$newcity%'
                        
                        UNION
                        
                        SELECT '' as name, 0 as city_id,
                            '' as region, 0 as region_id,
                            rs.name as country, rs.id as country_id, rs.alpha2 AS country_code,
                            city.pc_title as pc_title, url.url
                        FROM $dbrs rs
                        LEFT JOIN $dbc city ON city.pc_country_id = rs.id AND city.pc_city_id = 0 AND city.pc_region_id = 0
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rs.name LIKE '%$newcity%'
                        
                        ORDER BY country, region, name";
            $db->exec();
            while ($row = $db->fetch()) {
                $inbase[] = $row;
            }
            if (isset($inbase)) {
                foreach ($inbase as $id => $city) {
                    $translit = translit($city['name']);
                    $inbase[$id]['translit'] = $translit;
                    $db->sql = "SELECT * FROM $dbll WHERE lower(ll_name) = lower('$translit') LIMIT 1";
                    if ($db->exec()) {
                        $row = $db->fetch();
                        $inbase[$id]['lat'] = $row['ll_lat'];
                        $inbase[$id]['lon'] = $row['ll_lon'];
                        $latitude = $row['ll_lat'] >= 0 ? 'N' : 'S';
                        $latitude = $latitude . abs($row['ll_lat']);
                        $lolgitude = $row['ll_lon'] >= 0 ? 'E' : 'W';
                        $lolgitude = $lolgitude . abs($row['ll_lon']);
                        if ($latitude != 'N0' && $lolgitude != 'E0') {
                            $inbase[$id]['latlon'] = "{$row['ll_name']}: $latitude, $lolgitude";
                        }
                    }
                }
                $smarty->assign('inbase', $inbase);
            }
            //------------------- добавление произвольного региона --------------
            elseif (mb_strlen($newcity) >= 5) {
                $smarty->assign('freeplace', $newcity);
            }
            //-------------------------------------------------------------------
        }

        $smarty->assign('addregion', $newcity);
        $smarty->assign('already', $already);
        if (isset($this->user['userid'])) {
            $smarty->assign('adminlogined', $this->user['userid']);
        }
        $this->content = $smarty->fetch(_DIR_TEMPLATES . '/city/add.sm.html');
    }

    private function pageCity($db, $smarty) {
        //**************************************** СПИСОК **********************
        $dbc = $db->getTableName('pagecity');
        $dbr = $db->getTableName('region_url');
        $dbp = $db->getTableName('pagepoints');
        $dbcd = $db->getTableName('city_data');
        $dbrc = $db->getTableName('ref_country');
        $dbrr = $db->getTableName('ref_region');
        $dbws = $db->getTableName('wordstat');
        $where = (!$this->checkEdit()) ? "WHERE city.pc_text is not null" : '';
        $db->sql = "SELECT city.pc_id, city.pc_title, city.pc_latitude, city.pc_longitude,
                            city.pc_city_id, city.pc_region_id, city.pc_country_id,
                            url.url,
                            CHAR_LENGTH(city.pc_text) as len,
                            CHAR_LENGTH(city.pc_announcement) as anons_len,
                            city.pc_inwheretext,
                            city.pc_pagepath,
                            (SELECT count(pt_id) FROM $dbp WHERE pt_citypage_id = city.pc_id) as pts,
                            (SELECT count(cd_id) FROM $dbcd WHERE cd_pc_id = city.pc_id) as meta,
                            ws.ws_weight_max, ws.ws_position,
                            UNIX_TIMESTAMP(city.pc_lastup_date) AS last_update
                    FROM $dbc city
                        LEFT JOIN $dbr url ON url.uid = city.pc_url_id
                        LEFT JOIN $dbrc rc ON rc.id = city.pc_country_id
                        LEFT JOIN $dbrr rr ON rr.id = city.pc_region_id
                        LEFT JOIN $dbws ws ON ws.ws_city_id = city.pc_city_id AND ws.ws_city_title = city.pc_title
                $where
                    GROUP BY city.pc_id
                    ORDER BY rc.ordering, rc.name, rr.ordering, rr.name, url.url, city.pc_title";
        $db->exec();
        while ($row = $db->fetch()) {
            $row['pc_pagepath'] = strip_tags($row['pc_pagepath']);
            if ($row['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $row['last_update'];
            }
            $cities[] = $row;
        }
        $smarty->assign('tcity', $cities);
        if (isset($this->user['userid'])) {
            $smarty->assign('adminlogined', $this->user['userid']);
        }

        if ($this->checkEdit()) {
            $this->content = $smarty->fetch(_DIR_TEMPLATES . '/city/city.edit.sm.html');
        } else {
            $this->content = $smarty->fetch(_DIR_TEMPLATES . '/city/city.show.sm.html');
        }
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
