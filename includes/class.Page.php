<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        parent::__construct($db, $mod[0]); //встроеные модули
        //if ($mod[1]!=null) $this->getSubContent($this->md_id, $mod[1]);
        //$this->navibar = $this->getNavigation($this->md_id, $mod[1]);
        if ($this->content) {
            //
        } elseif ($this->content = $this->getPageByURL($mod)) {
            //
        } else {
            $this->getError('404');
        }
    }

    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

    public function getPageByURL($aurl) {
        $url = '';
        foreach ($aurl as $w) {
            if ($w != '') {
                $url .= '/' . $w;
            }
        }
        if ($url != '') {
            $regs = array();
            $url_parts_array = !empty($url) ? explode('/', $url) : array();
            $url_parts = array_pop($url_parts_array);
            if ($url_parts == 'map.html') {
                return $this->getPageMap($this->db, $this->smarty, $url);
            } elseif ($url_parts == 'index.html') {
                return $this->getPageCity($this->db, $this->smarty, $url);
            } elseif (preg_match('/object([0-9]+)\.html/i', $url_parts, $regs)) {
                return $this->getPageObject($this->db, $this->smarty, intval($regs[1]));
            } elseif (preg_match('/([a-z0-9_-]+)\.html/i', $url_parts, $regs)) {
                return $this->getPageObjectBySlug($regs[1]);
            } else {
                return $this->getPageCity($this->db, $this->smarty, $url);
            }
        } else {
            return FALSE;
        }
    }

    private function getPageObjectBySlug($slugline) {
        if (!$slugline) {
            return false;
        }

        $pts = new MPagePoints($this->db);
        $pcs = new MPageCities($this->db);

        $objects = $pts->searchSlugline($slugline);
        $object = isset($objects[0]) ? $objects[0] : false;
        if (!$object) {
            return false;
        }
        $this->canonical = $object['url_canonical'];
        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != $this->canonical) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: $this->canonical ");
            exit();
        }

        $city = $pcs->getItemByPk($object['pt_citypage_id']);

        $short = strip_tags($object['pt_description']);
        $short = mb_strlen($short) >= 100 ? mb_substr($short, 0, mb_strpos($short, ' ', 100), 'utf-8') : $short;
        $object['esc_name'] = htmlentities($object['pt_name'], ENT_QUOTES, 'utf-8');
        $object['map_zoom'] = ($object['pt_latlon_zoom']) ? $object['pt_latlon_zoom'] : 14;
        if ($object['pt_latitude'] && $object['pt_longitude']) {
            $object_lat_short = mb_substr($object['pt_latitude'], 0, 8);
            $object_lon_short = mb_substr($object['pt_longitude'], 0, 8);
            $object['gps_dec'] = (($object_lat_short >= 0) ? 'N' : 'S') . abs($object_lat_short) . ' ' . (($object_lon_short >= 0) ? 'E' : 'W') . abs($object_lon_short);
            $object['sw_ne_delta'] = 0.01;
            $object['sw_ne'] = array(
                'sw' => array('lat' => $object['pt_latitude'] - $object['sw_ne_delta'], 'lon' => $object['pt_longitude'] - $object['sw_ne_delta']),
                'ne' => array('lat' => $object['pt_latitude'] + $object['sw_ne_delta'], 'lon' => $object['pt_longitude'] + $object['sw_ne_delta']),
            );
            //$object['gps_deg'] = 0;
        }


        $this->lastedit_timestamp = $object['last_update'];
        $this->lastedit = gmdate('D, d M Y H:i:s', $this->lastedit_timestamp) . ' GMT';

        //------------------  s t a t i s t i c s  ------------------------
        $sp = new Statpoints($this->db);
        $sp->add($object['pt_id'], $this->getUserHash());

        $this->addTitle($city['pc_title']);
        $this->addTitle($object['esc_name']);
        if ($object['tr_sight']) {
            $this->addDescription('Достопримечательности ' . $city['pc_inwheretext']);
        }
        if (isset($object['gps_dec'])) {
            $this->addDescription('GPS-координаты');
        }
        $this->addDescription("{$object['tp_short']} {$city['pc_inwheretext']}");
        $this->addDescription($object['esc_name']);
        $this->addDescription($short);
        $this->addKeywords($city['pc_title']);
        $this->addKeywords($object['esc_name']);
        if (isset($object['gps_dec'])) {
            $this->addKeywords('координаты GPS');
        }
        $this->mainfile_js = _ER_REPORT ? ('../sys/static/?type=js&pack=point') : $this->globalsettings['res_js_point'];

        $this->smarty->assign('object', $object);
        $this->smarty->assign('city', $city);
        $this->smarty->assign('lists', $pts->getLists($object['pt_id']));

        return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/pagepoint.sm.html');
    }

    public function getSubContent($pid, $p_url) {
        global $db;
        $dbm = $this->db->getTableName('modules');
        $db->sql = "SELECT md_url, md_title, md_keywords, md_description, md_pagecontent
                    FROM $dbm WHERE md_active = '1' AND md_pid = '$pid'";
        $res = $db->exec();
        if (!$res) {
            $this->getError('404');
        }
        while ($row = $db->fetch($res)) {
            if ($row['md_url'] == $p_url) {
                $this->h1 .= ' ' . $this->globalsettings['title_delimiter'] . ' ' . $row['md_title'];
                $this->content = $row['md_pagecontent'];
                $this->addDescription($row['md_description']);
                $this->addKeywords($row['md_keywords']);
                $this->addTitle($row['md_title']);
                return true;
            }
        }
        $this->getError('404');
    }

    public function getNavigation($module_id, $sub_url) {
        global $db;
        $dbm = $this->db->getTableName('modules');
        $db->sql = "SELECT md_title, md_url FROM $dbm WHERE md_active = '1' AND md_id = '$module_id' LIMIT 1";
        $pres = $db->exec();
        $parent = $db->fetch();
        $db->sql = "SELECT md_title, md_url FROM $dbm WHERE md_active = '1' AND md_pid = '$module_id'";
        $res = $db->exec();
        if ($res) {
            while ($row = $db->fetch($res)) {
                $navi = array('url' => $row['md_url'], 'title' => $row['md_title'], 'active' => false);
                if ($row['md_url'] == $sub_url)
                    $navi['active'] = true;
                $navi_items[] = $navi;
            }
            if (isset($navi_items) && !empty($navi_items)) {
                global $smarty;
                $smarty->assign('parent', $parent);
                $smarty->assign('navi_items', $navi_items);
                return $smarty->fetch(_DIR_TEMPLATES . '/_main/navigation.sm.html');
            }
        } else {
            return '';
        }
    }

    private function getPageMap($db, $smarty, $url) {
        $this->ymaps_ver = 2;
        $dburl = $db->getTableName('region_url');
        $dbpc = $db->getTableName('pagecity');
        $dbpp = $db->getTableName('pagepoints');
        $dbsc = $db->getTableName('statcity');
        $dbpt = $db->getTableName('ref_pointtypes');
        $url = str_replace('/map.html', '', $url);

        $url = $db->getEscapedString($url);
        $db->sql = "SELECT city.*,
                            UNIX_TIMESTAMP(city.pc_lastup_date) AS last_update1,
                            (SELECT UNIX_TIMESTAMP(MAX(pt_lastup_date)) FROM $dbpp WHERE pt_citypage_id = city.pc_id) AS last_update2                           
                    FROM $dburl url
                    LEFT JOIN $dbpc city ON city.pc_id = url.citypage
                    WHERE url.url = '$url'";
        $db->exec();
        //$db->showSQL();
        if ($row = $db->fetch()) {
            $this->lastedit_timestamp = max(array($row['last_update1'], $row['last_update2']));
            $row['pc_zoom'] = ($row['pc_latlon_zoom']) ? $row['pc_latlon_zoom'] : 12;
            $row['pc_zoom'] ++;

            header("Location: /map/#center={$row['pc_longitude']},{$row['pc_latitude']}&zoom={$row['pc_zoom']}");
            exit();
        }
        $this->lastedit = gmdate('D, d M Y H:i:s', $this->lastedit_timestamp) . ' GMT';
        $smarty->assign('city', $row);
        $smarty->assign('point_types', $point_types);
        $smarty->assign('points_sight', $pnts_sight);
        $smarty->assign('points_servo', $pnts_service);
        $smarty->assign('page_url', $this->basepath);

        return $smarty->fetch(_DIR_TEMPLATES . '/_pages/pagemap.sm.html');
    }

    private function getPageCity($db, $smarty, $url) {
        $url_parts = explode('/', $url);
        if (array_pop($url_parts) == 'index.html') {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . str_replace("index.html", '', $url));
            exit();
        }

        $pcs = new MPageCities($this->db);
        $pts = new MPagePoints($this->db);
        $row = $pcs->getCityByUrl($url);

        if (!empty($row) && isset($row['pc_title']) && $row['pc_title'] != '') {
            $row['pc_zoom'] = ($row['pc_latlon_zoom']) ? $row['pc_latlon_zoom'] : 12;
            $this->lastedit_timestamp = $row['last_update'];

            //--------------------  c a n o n i c a l  ------------------------
            $this->canonical = $row['url_canonical'];
            if ($this->canonical != ($url . '/')) {
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: $this->canonical");
                exit();
            }

            $points_data = $pts->getPointsByCity($row['pc_id'], $this->checkEdit());

            if ($points_data['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $points_data['last_update'];
            }
            if ($this->checkEdit()) {
                $this->lastedit_timestamp = 0;
            }

            $this->lastedit = gmdate('D, d M Y H:i:s', $this->lastedit_timestamp) . ' GMT';

            $sc = new Statcity($this->db);
            $sc->add($row['pc_id'], $this->getUserHash());

            $this->addTitle($row['pc_title'] . ': достопримечательности');
            if ($row['pc_description']) {
                $this->addDescription($row['pc_description']);
            }
            $this->addDescription('Достопримечательности ' . $row['pc_inwheretext'] . ' с GPS-координатами');
            if ($row['pc_keywords']) {
                $this->addKeywords($row['pc_keywords']);
            }
            $this->addKeywords('достопримечательности ' . $row['pc_inwheretext']);
            $this->addKeywords('Координаты GPS');
            $this->addKeywords($row['pc_title_translit']);
            if ($row['pc_title_english'] && $row['pc_title_english'] != $row['pc_title_translit']) {
                $this->addKeywords($row['pc_title_english']);
            }
            if ($row['pc_title_synonym']) {
                $this->addKeywords($row['pc_title_synonym']);
            }

            $this->smarty->assign('city', $row);
            $this->smarty->assign('points', $points_data['points']);
            $this->smarty->assign('points_sight', $points_data['points_sight']);
            $this->smarty->assign('points_servo', $points_data['points_service']);
            $this->smarty->assign('page_url', $this->basepath);
            $this->smarty->assign('types_select', $points_data['types']);
            $this->smarty->assign('ptypes', array());
            $this->mainfile_js = _ER_REPORT ? ('../sys/static/?type=js&pack=city') : $this->globalsettings['res_js_city'];

            if ($this->checkEdit()) {
                return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/pagecity.edit.sm.html');
            } else {
                return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/pagecity.show.sm.html');
            }
        } else {
            return $this->getError('404');
        }
    }

    private function getPageObject($db, $smarty, $id) {
        if (!$id) {
            return false;
        }

        $pts = new MPagePoints($this->db);
        $object = $pts->getItemByPk($id);
        if (!$object || $object['pt_active'] == 0) {
            return false;
        }
        $this->canonical = $object['url_canonical'];
        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != $this->canonical) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: $this->canonical");
            exit();
        }

        $dbpt = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dbsp = $db->getTableName('statpoints');
        $dbrp = $db->getTableName('ref_pointtypes');
        $dburl = $db->getTableName('region_url');

        $uridata = explode('/', $_SERVER['REQUEST_URI']);
        array_pop($uridata);
        $url = implode('/', $uridata);


        $short = strip_tags($object['pt_description']);
        $short = mb_strlen($short) >= 100 ? mb_substr($short, 0, mb_strpos($short, ' ', 100), 'utf-8') : $short;
        $object['esc_name'] = htmlentities($object['pt_name'], ENT_QUOTES, 'utf-8');
        $object['map_zoom'] = ($object['pt_latlon_zoom']) ? $object['pt_latlon_zoom'] : 14;
        if ($object['pt_latitude'] && $object['pt_longitude']) {
            $object_lat_short = mb_substr($object['pt_latitude'], 0, 8);
            $object_lon_short = mb_substr($object['pt_longitude'], 0, 8);
            $object['gps_dec'] = (($object_lat_short >= 0) ? 'N' : 'S') . abs($object_lat_short) . ' ' . (($object_lon_short >= 0) ? 'E' : 'W') . abs($object_lon_short);
            $object['sw_ne_delta'] = 0.01;
            $object['sw_ne'] = array(
                'sw' => array('lat' => $object['pt_latitude'] - $object['sw_ne_delta'], 'lon' => $object['pt_longitude'] - $object['sw_ne_delta']),
                'ne' => array('lat' => $object['pt_latitude'] + $object['sw_ne_delta'], 'lon' => $object['pt_longitude'] + $object['sw_ne_delta']),
            );
            //$object['gps_deg'] = 0;
        }
        $smarty->assign('object', $object);

        $this->lastedit_timestamp = $object['last_update'];
        $this->lastedit = gmdate('D, d M Y H:i:s', $this->lastedit_timestamp) . ' GMT';

        $db->sql = "SELECT pc.pc_title, pc.pc_inwheretext, pc.pc_pagepath, pc.pc_url_id
                    FROM $dbpc pc
                    WHERE pc.pc_id = '{$object['pt_citypage_id']}'";
        $db->exec();
        $city = $db->fetch();
        $smarty->assign('city', $city);

        //------------------  s t a t i s t i c s  ------------------------
        $hash = $this->getUserHash();
        $db->sql = "INSERT INTO $dbsp (sp_pagepoint_id, sp_date, sp_hash) VALUES ('$id', now(), '$hash')
                        ON DUPLICATE KEY UPDATE sp_date = now()";
        $db->exec();

        $this->addTitle($city['pc_title']);
        $this->addTitle($object['esc_name']);
        if ($object['tr_sight']) {
            $this->addDescription('Достопримечательности ' . $city['pc_inwheretext']);
        }
        if (isset($object['gps_dec'])) {
            $this->addDescription('GPS-координаты');
        }
        $this->addDescription("{$object['tp_short']} {$city['pc_inwheretext']}");
        $this->addDescription($object['esc_name']);
        $this->addDescription($short);
        $this->addKeywords($city['pc_title']);
        $this->addKeywords($object['esc_name']);
        $this->mainfile_js = _ER_REPORT ? ('../sys/static/?type=js&pack=point') : $this->globalsettings['res_js_point'];
        if (isset($object['gps_dec'])) {
            $this->addKeywords('координаты GPS');
        }

        return $smarty->fetch(_DIR_TEMPLATES . '/_pages/pagepoint.sm.html');
    }

}
