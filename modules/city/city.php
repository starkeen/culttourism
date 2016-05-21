<?php

class Page extends PageCommon {

    public function __construct($module_id, $page_id) {
        $db = FactoryDB::db();
        parent::__construct($db, 'city', $page_id);

        if ($page_id[1] == '') {
            return $this->pageCity();
        } elseif ($page_id[1] == 'add') {
            return $this->addCity();
        } elseif ($page_id[1] == 'detail') {
            return $this->detailCity();
        } elseif ($page_id[1] == 'meta') {
            return $this->metaCity();
        } elseif ($page_id[1] == 'weather' && isset($_GET['lat']) && isset($_GET['lon'])) {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 1, 2050);
            $this->isAjax = true;
            return $this->getBlockWeather($_GET['lat'], $_GET['lon']);
        } else {
            return $this->getError('404');
        }
    }

    //****************************************  БЛОК  ПОГОДЫ  ******************
    private function getBlockWeather($lat, $lon) {
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

        $curl = new Curl($this->db);
        $curl->setTTL(3600); //кэшируем запросы на час
        $curl->config(CURLOPT_TIMEOUT, 2);
        $curl->config(CURLOPT_HEADER, 0);
        $curl->config(CURLOPT_SSL_VERIFYPEER, false);
        $curl->config(CURLOPT_FAILONERROR, true);

        $url = 'http://api.openweathermap.org/data/2.5/weather?lat='
                . floatval($lat) . '&lon=' . floatval($lon)
                . '&APPID=' . $this->globalsettings['app_openweather_key'];
        $result = $curl->get($url);
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
            $weather_data['humidity'] = !empty($response->main->humidity) ? round($response->main->humidity) : null;
            $weather_data['windspeed'] = round($response->wind->speed, 1);
            $weather_data['winddeg'] = !empty($response->wind->deg) ? $response->wind->deg : 0;
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

    //**************************************  ПОГОДА ПО КОДУ  ******************
    private function getWeaterConditionsByCode($code) {
        $wc = new MWeatherCodes($this->db);
        $row = $wc->getItemByPk($code);
        if ($row['wc_id'] != 0) {
            return array('main' => $row['wc_main'], 'description' => $row['wc_description']);
        } else {
            return false;
        }
    }

    //****************************************  ТАБЛИЦА МЕТА  ******************
    private function metaCity() {
        $dbcd = $this->db->getTableName('city_data');
        $dbcf = $this->db->getTableName('city_fields');

        $pc = new MPageCities($this->db);

        if (isset($_POST['act'])) {
            if (!$this->checkEdit()) {
                return $this->getError('403');
            }
            $uid = $this->getUserId();
            switch ($_POST['act']) {
                case 'add':
                    $cf_id = intval($_POST['cf']);
                    $value = trim($_POST['val']);
                    $city_id = intval($_POST['cpid']);
                    $this->db->sql = "DELETE FROM $dbcd WHERE cd_pc_id = :city_id AND cd_cf_id = :cf_id";
                    $this->db->execute(array(
                        ':city_id' => $city_id,
                        ':cf_id' => $cf_id,
                    ));

                    if ($value != '') {
                        $this->db->sql = "INSERT INTO $dbcd SET cd_pc_id = :city_id, cd_cf_id = :cf_id, cd_value = :cd_value";
                        $this->db->execute(array(
                            ':city_id' => $city_id,
                            ':cf_id' => $cf_id,
                            ':cd_value' => $value,
                        ));
                    }
                    $this->db->sql = "SELECT * FROM  $dbcf WHERE cf_id = :cf_id";
                    $this->db->execute(array(
                        ':cf_id' => $cf_id,
                    ));
                    $row = $this->db->fetch();
                    $pc->updateByPk($city_id, array(
                        'pc_lastup_user' => $uid
                    ));
                    echo $row['cf_title'];
                    break;
                case 'del':
                    $cf_id = intval($_POST['cf']);
                    $city_id = intval($_POST['cpid']);
                    $this->db->sql = "DELETE FROM $dbcd WHERE cd_pc_id = :city_id AND cd_cf_id = :cf_id";
                    $this->db->execute(array(
                        ':city_id' => $city_id,
                        ':cf_id' => $cf_id,
                    ));
                    $pc->updateByPk($city_id, array(
                        'pc_lastup_user' => $uid
                    ));
                    echo 'ok';
                    break;
                case 'edit':
                    $cf_id = intval($_POST['cf']);
                    $city_id = intval($_POST['cpid']);
                    $value = trim($_POST['val']);
                    if ($value != '') {
                        $this->db->sql = "UPDATE $dbcd SET cd_value = :cd_value WHERE cd_pc_id = :city_id AND cd_cf_id = :cf_id";
                        $this->db->execute(array(
                            ':city_id' => $city_id,
                            ':cf_id' => $cf_id,
                            ':cd_value' => $value,
                        ));
                    }
                    $pc->updateByPk($city_id, array(
                        'pc_lastup_user' => $uid
                    ));
                    echo 'ok';
                    break;
            }
        } else {
            $this->db->sql = "SELECT cf_title, cd_value
                                FROM $dbcd cd
                                    LEFT JOIN $dbcf cf ON cf.cf_id = cd.cd_cf_id
                                WHERE cd.cd_pc_id = :pc_id
                                    AND cd.cd_value != ''
                                    AND cf.cf_active = 1
                                ORDER BY cf_order";
            $this->db->execute(array(
                ':pc_id' => intval($_GET['id'])
            ));
            $metas = $this->db->fetchAll();

            $this->smarty->assign('metas', $metas);
            header('Content-Type: text/html; charset=utf-8');
            $this->smarty->display(_DIR_TEMPLATES . '/city/meta.sm.html');
        }
        exit();
    }

    //**************************************** РЕДАКТИРОВАНИЕ ******************
    private function detailCity() {
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        if (!isset($_GET['city_id'])) {
            return $this->getError('404');
        }
        $city_id = intval($_GET['city_id']);
        if (!$city_id) {
            return $this->getError('404');
        }

        $pc = new MPageCities($this->db);

        $dbcd = $this->db->getTableName('city_data');
        $dbcf = $this->db->getTableName('city_fields');
        $dbws = $this->db->getTableName('wordstat');

        if (isset($_POST) && !empty($_POST)) {
            //print_x($_POST);
            $pc->updateByPk($city_id, array(
                'pc_keywords' => $_POST['keywds'],
                'pc_description' => $_POST['descr'],
                'pc_announcement' => $_POST['anons'],
                'pc_latitude' => $_POST['latitude'],
                'pc_longitude' => $_POST['longitude'],
                'pc_osm_id' => intval($_POST['osm_id']),
                'pc_inwheretext' => $_POST['inwhere'],
                'pc_title_synonym' => $_POST['synonym'],
                'pc_title_english' => $_POST['title_eng'],
                'pc_title_translit' => $_POST['translit'],
                'pc_website' => $_POST['web'],
                'pc_lastup_user' => $this->getUserId(),
            ));
            $city = $pc->getItemByPk($city_id);
            //$aurl = explode('/', $_POST['url']);

            header("Location: {$city['url']}");
            exit();
        }

        $citypage = $pc->getItemByPk($city_id);

        $this->db->sql = "SELECT *
                    FROM $dbcd cd
                        LEFT JOIN $dbcf cf ON cf.cf_id = cd.cd_cf_id
                    WHERE cd.cd_pc_id = :pc_id
                    ORDER BY cf_order";
        $this->db->execute(array(
            ':pc_id' => $city_id,
        ));
        $meta = $this->db->fetchAll();

        $this->db->sql = "SELECT *
                    FROM $dbcf
                    WHERE cf_id NOT IN (SELECT cd_cf_id FROM $dbcd WHERE cd_pc_id = :pc_id)
                    ORDER BY cf_order";
        $this->db->execute(array(
            ':pc_id' => $city_id,
        ));
        $ref_meta = $this->db->fetchAll();

        $this->db->sql = "SELECT *
                    FROM $dbws
                    WHERE ws_city_title = :pc_title
                    LIMIT 1";
        $this->db->execute(array(
            ':pc_title' => $citypage['pc_title'],
        ));
        $yandex = $this->db->fetch();

        $this->smarty->assign('city', $citypage);
        $this->smarty->assign('baseurl', $this->basepath);
        $this->smarty->assign('meta', $meta);
        $this->smarty->assign('ref_meta', $ref_meta);
        $this->smarty->assign('yandex', $yandex);

        $this->lastedit_timestamp = $citypage['last_update'];

        $this->smarty->assign('adminlogined', isset($this->user['userid']) ? $this->getUserId() : 0);
        $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/city/details.sm.html');
    }

    //**************************************** ДОБАВЛЕНИЕ ******************
    private function addCity() {
        $newcity = '';
        $inbase = array();
        $already = array();
        $pc = new MPageCities($this->db);
        if (isset($_POST) && !empty($_POST)) {
            $dbc = $this->db->getTableName('pagecity');
            $dbu = $this->db->getTableName('region_url');
            $cid = $pc->insert(array(
                'pc_title' => $_POST['city_name'],
                'pc_city_id' => $_POST['city_id'],
                'pc_region_id' => $_POST['region_id'],
                'pc_country_id' => $_POST['country_id'],
                'pc_country_code' => $_POST['country_code'],
                'pc_latitude' => $_POST['latitude'],
                'pc_longitude' => $_POST['longitude'],
                'pc_rank' => 0,
                'pc_title_translit' => translit($_POST['city_name']),
                'pc_title_english' => translit($_POST['city_name']),
                'pc_inwheretext' => $_POST['city_name'],
                'pc_add_user' => $this->getUserId(),
            ));
            if ($cid > 0) {
                header("location: /city/detail/?city_id=$cid");
                exit();
            }
        } elseif (!empty($_GET['cityname'])) {
            $newcity = trim($_GET['cityname']);
            $dbc = $this->db->getTableName('pagecity');
            $dbu = $this->db->getTableName('region_url');
            $dbrc = $this->db->getTableName('ref_city');
            $dbrr = $this->db->getTableName('ref_region');
            $dbrs = $this->db->getTableName('ref_country');
            $dbll = $this->db->getTableName('ref_citylatlon');
            //------------------- поиск уже имеющихся --------------
            $this->db->sql = "SELECT url.url, city.pc_title
                        FROM $dbc city
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE city.pc_title LIKE :newcity1 OR city.pc_title_synonym LIKE :newcity2";
            $this->db->execute(array(
                ':newcity1' => '%' . $newcity . '%',
                ':newcity2' => '%' . $newcity . '%',
            ));
            while ($row = $this->db->fetch()) {
                $already[$row['url']] = $row['pc_title'];
            }
            //------------------- поиск в справочнике регионов --------------
            $this->db->sql = "SELECT rc.name as name, rc.id as city_id,
                            rr.name as region, rr.id as region_id,
                            rs.name as country, rs.id as country_id, rs.alpha2 AS country_code,
                            city.pc_title as pc_title, url.url
                        FROM $dbrc rc
                        LEFT JOIN $dbrr rr ON rr.id = rc.region_id
                        LEFT JOIN $dbrs rs ON rs.id = rc.country_id
                        LEFT JOIN $dbc city ON city.pc_city_id = rc.id
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rc.name LIKE :newcity1
                        
                        UNION
                        
                        SELECT '' as name, 0 as city_id,
                            rr.name as region, rr.id as region_id,
                            rs.name as country, rs.id as country_id, rs.alpha2 AS country_code,
                            city.pc_title as pc_title, url.url
                        FROM $dbrr rr
                        LEFT JOIN $dbrs rs ON rs.id = rr.country_id
                        LEFT JOIN $dbc city ON city.pc_region_id = rr.id AND city.pc_city_id = 0
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rr.name LIKE :newcity2
                        
                        UNION
                        
                        SELECT '' as name, 0 as city_id,
                            '' as region, 0 as region_id,
                            rs.name as country, rs.id as country_id, rs.alpha2 AS country_code,
                            city.pc_title as pc_title, url.url
                        FROM $dbrs rs
                        LEFT JOIN $dbc city ON city.pc_country_id = rs.id AND city.pc_city_id = 0 AND city.pc_region_id = 0
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rs.name LIKE :newcity3
                        
                        ORDER BY country, region, name";
            $this->db->execute(array(
                ':newcity1' => '%' . $newcity . '%',
                ':newcity2' => '%' . $newcity . '%',
                ':newcity3' => '%' . $newcity . '%',
            ));
            while ($row = $this->db->fetch()) {
                $inbase[] = $row;
            }
            foreach ($inbase as $id => $city) {
                $translit = translit($city['name']);
                $inbase[$id]['translit'] = $translit;
                $this->db->sql = "SELECT * FROM $dbll WHERE LOWER(ll_name) = LOWER(:name) LIMIT 1";
                $state = $this->db->execute(array(
                    ':name' => $translit,
                ));
                if ($state) {
                    $row = $this->db->fetch();
                    $inbase[$id]['lat'] = $row['ll_lat'];
                    $inbase[$id]['lon'] = $row['ll_lon'];
                    $latitude = $row['ll_lat'] >= 0 ? 'N' : 'S';
                    $latitude = $latitude . abs($row['ll_lat']);
                    $lolgitude = $row['ll_lon'] >= 0 ? 'E' : 'W';
                    $lolgitude = $lolgitude . abs($row['ll_lon']);
                    if ($latitude != 'N0' && $lolgitude != 'E0') {
                        $inbase[$id]['latlon'] = "{$row['ll_name']}: $latitude, $lolgitude";
                    } else {
                        $inbase[$id]['latlon'] = null;
                    }
                }
            }

            //-------------------------------------------------------------------
        }

        $this->smarty->assign('inbase', $inbase);
        $this->smarty->assign('addregion', $newcity);
        $this->smarty->assign('already', $already);
        $this->smarty->assign('freeplace', mb_strlen($newcity) >= 5 ? $newcity : null);
        $this->smarty->assign('adminlogined', isset($this->user['userid']) ? $this->user['userid'] : null);
        $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/city/add.sm.html');
    }

    //**************************************** СПИСОК **********************
    private function pageCity() {
        $dbc = $this->db->getTableName('pagecity');
        $dbr = $this->db->getTableName('region_url');
        $dbrc = $this->db->getTableName('ref_country');
        $dbrr = $this->db->getTableName('ref_region');
        $dbws = $this->db->getTableName('wordstat');
        $where = (!$this->checkEdit()) ? "WHERE city.pc_text is not null" : '';
        $this->db->sql = "SELECT city.pc_id, city.pc_title, city.pc_latitude, city.pc_longitude,
                            city.pc_city_id, city.pc_region_id, city.pc_country_id,
                            url.url,
                            CHAR_LENGTH(city.pc_text) as len,
                            CHAR_LENGTH(city.pc_announcement) as anons_len,
                            city.pc_inwheretext,
                            city.pc_pagepath,
                            city.pc_count_points,
                            city.pc_count_metas,
                            city.pc_count_photos,
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
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            $row['pc_pagepath'] = strip_tags($row['pc_pagepath']);
            if ($row['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $row['last_update'];
            }
            $cities[] = $row;
        }

        $this->smarty->assign('tcity', $cities);
        $this->smarty->assign('adminlogined', isset($this->user['userid']) ? $this->user['userid'] : 0);

        if ($this->checkEdit()) {
            $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/city/city.edit.sm.html');
        } else {
            $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/city/city.show.sm.html');
        }
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
