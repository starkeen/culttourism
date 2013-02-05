<?php

class Page extends PageCommon {

    public function __construct($module_id, $page_id) {
        global $db;
        global $smarty;
        parent::__construct($db, 'city', $page_id);

        if ($page_id[1] == '')
            return $this->pageCity($db, $smarty);
        elseif ($page_id[1] == 'add')
            return $this->addCity($db, $smarty);
        elseif ($page_id[1] == 'detail')
            return $this->detailCity($db, $smarty);
        else
            return $this->getError('404');
    }

    private function detailCity($db, $smarty) {
        //**************************************** РЕДАКТИРОВАНИЕ ******************
        if (!$this->checkEdit())
            return $this->getError('403');
        if (!isset($_GET['city_id']))
            return $this->getError('404');
        $city_id = cut_trash_int($_GET['city_id']);
        if (!$city_id)
            return $this->getError('404');

        $uid = $this->getUserId();
        $dbc = $db->getTableName('pagecity');
        $dbu = $db->getTableName('region_url');

        if (isset($_POST) && !empty($_POST)) {
            //print_x($_POST);
            $pc_keywords = cut_trash_string($_POST['keywds']);
            $pc_description = cut_trash_text($_POST['descr']);
            $pc_latitude = cut_trash_string($_POST['latitude']);
            $pc_latitude = str_replace(',', '.', $pc_latitude);
            $pc_longitude = cut_trash_string($_POST['longitude']);
            $pc_longitude = str_replace(',', '.', $pc_longitude);
            $pc_inwheretext = cut_trash_string($_POST['inwhere']);
            $pc_title_english = cut_trash_string($_POST['title_eng']);
            $pc_title_translit = cut_trash_string($_POST['translit']);
            $pc_title_synonym = cut_trash_string($_POST['synonym']);
            $pc_website = cut_trash_string($_POST['web']);
            $url = cut_trash_string($_POST['url']);
            $db->sql = "UPDATE $dbc SET
                        pc_keywords = '$pc_keywords', pc_description = '$pc_description',
                        pc_latitude = '$pc_latitude', pc_longitude = '$pc_longitude',
                        pc_inwheretext = '$pc_inwheretext', pc_title_synonym = '$pc_title_synonym',
                        pc_title_english = '$pc_title_english', pc_title_translit = '$pc_title_translit',
                        pc_website = '$pc_website',
                        pc_lastup_date = now(), pc_lastup_user = '$uid'
                        WHERE pc_id = '$city_id'";
            $db->exec();
            $aurl = explode('/', $url);
            $nurl = '';
            foreach ($aurl as $u) {
                if ($u != '')
                    $nurl .= '/' . strtolower(str_replace(' ', '_', translit($u)));
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
        }

        $db->sql = "SELECT c.pc_id, c.pc_title, c.pc_keywords, c.pc_description,
                    c.pc_latitude, c.pc_longitude, c.pc_inwheretext, c.pc_title_translit, c.pc_title_english,
                    c.pc_title_synonym, c.pc_website,
                    u.url
                    FROM $dbc c
                    LEFT JOIN $dbu u ON u.uid = c.pc_url_id
                    WHERE pc_id = '$city_id'";
        $db->exec();
        $citypage = $db->fetch();

        $smarty->assign('city', $citypage);
        $smarty->assign('baseurl', $this->basepath);

        if (isset($this->user['userid']))
            $smarty->assign('adminlogined', $this->getUserId());
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
            $lat = cut_trash_string($_POST['latitude']);
            $lat = str_replace(',', '.', $lat);
            $lon = cut_trash_string($_POST['longitude']);
            $lon = str_replace(',', '.', $lon);
            $uid = $this->getUserId();
            $dbc = $db->getTableName('pagecity');
            $dbu = $db->getTableName('region_url');
            $translit = translit($city_name);
            $db->sql = "INSERT INTO $dbc SET
                        pc_title = '$city_name', pc_city_id = '$city_id', pc_region_id = '$region_id', pc_country_id = '$country_id',
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
            while ($row = $db->fetch()) {
                $already[$row['url']] = $row['pc_title'];
            }
            if (isset($already))
                $smarty->assign('already', $already);
            //------------------- поиск в справочнике регионов --------------
            $db->sql = "SELECT rc.name as name, rc.id as city_id, rr.name as region, rr.id as region_id,
                            rs.name as country, rs.id as country_id,
                            city.pc_title as pc_title, url.url
                        FROM $dbrc rc
                        LEFT JOIN $dbrr rr ON rr.id = rc.region_id
                        LEFT JOIN $dbrs rs ON rs.id = rc.country_id
                        LEFT JOIN $dbc city ON city.pc_city_id = rc.id
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rc.name LIKE '%$newcity%'
                        UNION
                        SELECT '' as name, 0 as city_id, rr.name as region, rr.id as region_id,
                            rs.name as country, rs.id as country_id,
                            city.pc_title as pc_title, url.url
                        FROM $dbrr rr
                        LEFT JOIN $dbrs rs ON rs.id = rr.country_id
                        LEFT JOIN $dbc city ON city.pc_region_id = rr.id AND city.pc_city_id = 0
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rr.name LIKE '%$newcity%'
                        UNION
                        SELECT '' as name, 0 as city_id, '' as region, 0 as region_id,
                            rs.name as country, rs.id as country_id,
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
                        if ($latitude != 'N0' && $lolgitude != 'E0')
                            $inbase[$id]['latlon'] = "{$row['ll_name']}: $latitude, $lolgitude";
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
        if (isset($this->user['userid']))
            $smarty->assign('adminlogined', $this->user['userid']);
        $this->content = $smarty->fetch(_DIR_TEMPLATES . '/city/add.sm.html');
    }

    private function pageCity($db, $smarty) {
        //**************************************** СПИСОК **********************
        $dbc = $db->getTableName('pagecity');
        $dbr = $db->getTableName('region_url');
        $dbp = $db->getTableName('pagepoints');
        $where = (!$this->checkEdit()) ? "WHERE city.pc_text is not null" : '';
        $db->sql = "SELECT city.pc_id, city.pc_title, city.pc_latitude, city.pc_longitude,
                            city.pc_city_id, city.pc_region_id, city.pc_country_id,
                            url.url, char_length(city.pc_text) as len, city.pc_inwheretext,
                            city.pc_pagepath,
                            (SELECT count(pt_id) FROM $dbp WHERE pt_citypage_id = city.pc_id) as pts,
                            UNIX_TIMESTAMP(city.pc_lastup_date) AS last_update
                    FROM $dbc city
                    LEFT JOIN $dbr url ON url.uid = city.pc_url_id
                $where
                    ORDER BY  city.pc_country_id, city.pc_region_id, city.pc_city_id, url.url, city.pc_title";
        $res = $db->exec();
        while ($row = $db->fetch()) {
            $row['pc_pagepath'] = strip_tags($row['pc_pagepath']);
            $cities[] = $row;
            if ($row['last_update'] > $this->lastedit_timestamp)
                $this->lastedit_timestamp = $row['last_update'];
        }
        $smarty->assign('tcity', $cities);
        if (isset($this->user['userid']))
            $smarty->assign('adminlogined', $this->user['userid']);

        if ($this->checkEdit())
            $this->content = $smarty->fetch(_DIR_TEMPLATES . '/city/city.edit.sm.html');
        else
            $this->content = $smarty->fetch(_DIR_TEMPLATES . '/city/city.show.sm.html');
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}

?>
