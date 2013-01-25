<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        parent::__construct($db, $mod[0]); //встроеные модули
        //if ($mod[1]!=null) $this->getSubContent($this->md_id, $mod[1]);
        //$this->navibar = $this->getNavigation($this->md_id, $mod[1]);
        if ($this->content)
            ;
        elseif ($this->content = $this->getPageByURL($mod))
            ;
        else
            $this->getError('404');
    }

    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

    public function getPageByURL($aurl) {
        global $db;
        $url = '';
        foreach ($aurl as $w)
            if ($w != '')
                $url .= '/' . $w;
        if ($url == '')
            return FALSE;
        else {
            global $smarty;
            if (ereg('^object([[:digit:]]+).html$', array_pop(explode('/', $url)), $regs))
                return $this->getPageObject($db, $smarty, intval($regs[1]));
            elseif (array_pop(explode('/', $url)) == 'map.html')
                return $this->getPageMap($db, $smarty, $url);
            else
                return $this->getPageCity($db, $smarty, $url);
        }
    }

    public function getSubContent($pid, $p_url) {
        global $db;
        $dbm = $this->db->getTableName('modules');
        $db->sql = "SELECT md_url, md_title, md_keywords, md_description, md_pagecontent
                    FROM $dbm WHERE md_active = '1' AND md_pid = '$pid'";
        $res = $db->exec();
        if (!$res)
            $this->getError('404');
        while ($row = mysql_fetch_assoc($res)) {
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
            while ($row = mysql_fetch_assoc($res)) {
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

        $url = mysql_real_escape_string($url);
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
            $row['pc_zoom']++;

            //--------------------  c a n o n i c a l  -------------------------
            $db->sql = "SELECT url FROM $dburl WHERE uid = '{$row['pc_url_id']}'";
            $db->exec();
            $canonical_url = $db->fetch();
            if ($canonical_url['url'] != '')
                $this->canonical = $canonical_url['url'] . '/map.html';

            //----------------------  l e g e n d   ----------------------------
            $db->sql = "SELECT * FROM $dbpt ORDER BY tr_order";
            $db->exec();
            $point_types = array();
            while ($pts = $db->fetch()) {
                $point_types[] = $pts;
            }

            //--------------------  s t a t i s t i c s  -----------------------
            $hash = $this->getUserHash();
            $db->sql = "INSERT INTO $dbsc (sc_citypage_id, sc_date, sc_hash) VALUES ('{$row['pc_id']}', now(), '$hash')
                        ON DUPLICATE KEY UPDATE sc_date = now()";
            $db->exec();

            //---------------------  m e t a   k e y s   -----------------------
            $this->addTitle("Карта достопримечательностей {$row['pc_inwheretext']}");
            if ($row['pc_description'])
                $this->addDescription($row['pc_description']);
            $this->addDescription('Карта и схема расположения достопримечательностей ' . $row['pc_inwheretext']);
            if ($row['pc_keywords'])
                $this->addKeywords($row['pc_keywords']);
            $this->addKeywords('достопримечательности ' . $row['pc_inwheretext']);
            $this->addKeywords('Координаты GPS');
            $this->addKeywords($row['pc_title_translit']);
            if ($row['pc_title_english'] && $row['pc_title_english'] != $row['pc_title_translit'])
                $this->addKeywords($row['pc_title_english']);
            if ($row['pc_title_synonym'])
                $this->addKeywords($row['pc_title_synonym']);
            $this->addKeywords('карта');
            $this->addKeywords('схема');

            $this->isCounters = 1;
            $this->getCounters();
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
        $dburl = $db->getTableName('region_url');
        $dbpc = $db->getTableName('pagecity');
        $dbpt = $db->getTableName('pagepoints');
        $dbrp = $db->getTableName('ref_pointtypes');

        $url = mysql_real_escape_string($url);

        $db->sql = "SELECT city.*
                    FROM $dburl url
                    LEFT JOIN $dbpc city ON city.pc_id = url.citypage
                    WHERE url.url = '$url'";

        $res = $db->exec();
        if (!$res)
            return FALSE;
        if ($row = $db->fetch()) {
            $row['pc_zoom'] = ($row['pc_latlon_zoom']) ? $row['pc_latlon_zoom'] : 12;

            //--------------------  c a n o n i c a l  ------------------------
            $db->sql = "SELECT url FROM $dburl WHERE uid = '{$row['pc_url_id']}'";
            $db->exec();
            $canonical_url = $db->fetch();
            if ($canonical_url['url'] != '') {
                $this->canonical = $canonical_url['url'] . '/';
                if ($canonical_url['url'] != $url) {
                    //$this->getError('301', "{$canonical_url['url']}/");
                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: {$canonical_url['url']}/");
                }
            }


            //----------------------  в н у т р и  ------------------------
            $row['region_in'] = array();
            if ($row['pc_region_id'] > 0 && $row['pc_city_id'] == 0) {
                $db->sql = "SELECT pc.pc_title, url.url, pc.pc_inwheretext,
                                    UNIX_TIMESTAMP(pc.pc_add_date) AS last_update
                            FROM $dbpc pc
                            LEFT JOIN $dburl url ON url.uid = pc.pc_url_id
                            WHERE pc.pc_region_id = '{$row['pc_region_id']}'
                            AND pc.pc_city_id != 0
                            ORDER BY pc.pc_rank DESC, pc.pc_title";
                $db->exec();
                while ($subcity = $db->fetch()) {
                    $row['region_in'][] = array('title' => $subcity['pc_title'], 'url' => $subcity['url'], 'where' => $subcity['pc_inwheretext']);
                    if ($subcity['last_update'] > $this->lastedit_timestamp)
                        $this->lastedit_timestamp = $subcity['last_update'];
                }
            }
            //----------------------  р я д о м  ------------------------
            $row['region_near'] = array();
            if ($row['pc_region_id'] > 0 && $row['pc_city_id'] > 0) {
                $db->sql = "SELECT pc.pc_title, url.url, pc.pc_inwheretext,
                                    ROUND(1000 * (ABS(pc.pc_latitude - {$row['pc_latitude']}) + ABS(pc.pc_longitude - {$row['pc_longitude']}))) AS delta_sum,
                                    UNIX_TIMESTAMP(pc.pc_add_date) AS last_update
                            FROM $dbpc pc
                            LEFT JOIN $dburl url ON url.uid = pc.pc_url_id
                            WHERE pc.pc_city_id != 0
                            AND pc.pc_title != '{$row['pc_title']}'
                            AND pc.pc_latitude > 0 AND pc.pc_longitude > 0
                            HAVING delta_sum < 5000
                            ORDER BY delta_sum
                            LIMIT 10";
                //$db->showSQL();
                $db->exec();
                while ($subcity = $db->fetch()) {
                    $row['region_near'][] = array(
                        'title' => $subcity['pc_title'],
                        'url' => $subcity['url'],
                        'where' => $subcity['pc_inwheretext'],
                    );
                    if ($subcity['last_update'] > $this->lastedit_timestamp)
                        $this->lastedit_timestamp = $subcity['last_update'];
                }
            }
            /*
              if ($row['pc_region_id'] > 0 && $row['pc_city_id'] > 0) {
              $db->sql = "SELECT pc.pc_title, url.url, pc.pc_inwheretext
              FROM $dbpc pc
              LEFT JOIN $dburl url ON url.uid = pc.pc_url_id
              WHERE pc.pc_region_id = '{$row['pc_region_id']}'
              AND pc.pc_city_id != 0
              AND pc.pc_title != '{$row['pc_title']}'
              ORDER BY RAND()";
              $db->exec();
              while ($subcity = $db->fetch()) {
              $row['region_near'][] = array('title' => $subcity['pc_title'], 'url' => $subcity['url'], 'where' => $subcity['pc_inwheretext']);
              }
              }
             */

            //----------------------  т о ч к и  ------------------------
            $db->sql = "SELECT pt.*, pt.pt_id, pt.pt_name, pt.pt_type_id, pt.pt_description,
                        pt_latitude, pt_longitude, pt_latlon_zoom,
                                rp.tp_name, rp.tp_icon, rp.tp_short, rp.tr_sight, rp.tp_icon,
                                UNIX_TIMESTAMP(pt.pt_lastup_date) AS last_update
                        FROM $dbpt pt
                        LEFT JOIN $dbrp rp ON rp.tp_id = pt.pt_type_id
                        WHERE pt.pt_citypage_id = '{$row['pc_id']}'
                        AND pt.pt_active = 1
                        ORDER BY rp.tr_sight desc, pt.pt_rank desc, rp.tr_order, pt.pt_name";
            //$db->showSQL();
            $db->exec();
            $points = array();
            $pnts_sight = array();
            $pnts_service = array();
            $alltypes = array();
            while ($point = $db->fetch()) {
                $alltypes[$point['tr_sight']][$point['pt_type_id']] = array('short' => $point['tp_short'], 'full' => $point['tp_name'], 'icon' => $point['tp_icon']);

                $short_lenght = 300;
                $point['short'] = html_entity_decode(strip_tags($point['pt_description']), ENT_QUOTES, 'utf-8');
                $short_end = @mb_strpos($point['short'], '.', $short_lenght, 'utf-8');
                if (mb_strlen($point['short']) >= $short_lenght && $short_end)
                    $point['short'] = mb_substr($point['short'], 0, $short_end, 'utf-8') . '&hellip;';

                $point['pt_name'] = htmlentities($point['pt_name'], ENT_QUOTES, 'UTF-8', false);

                $point_lat = $point['pt_latitude'];
                $point_lon = $point['pt_longitude'];
                if ($point_lat && $point_lon) {
                    $point_lat_short = mb_substr($point_lat, 0, 8);
                    $point_lon_short = mb_substr($point_lon, 0, 8);
                    if ($point_lat >= 0)
                        $point_lat_w = "N$point_lat_short";
                    else
                        $point_lat_w = "S$point_lat_short";
                    if ($point_lon >= 0)
                        $point_lon_w = "E$point_lon_short";
                    else
                        $point_lon_w = "W$point_lon_short";
                    $point['gps_dec'] = "$point_lat_w $point_lon_w";
                } else {
                    $point['gps_dec'] = null;
                }
                $points[] = $point;
                if ($point['tr_sight'] == 1)
                    $pnts_sight[] = $point;
                else
                    $pnts_service[] = $point;

                if ($point['last_update'] > $this->lastedit_timestamp)
                    $this->lastedit_timestamp = $point['last_update'];
            }
            $this->lastedit = gmdate('D, d M Y H:i:s', $this->lastedit_timestamp) . ' GMT';

            $smarty->assign('city', $row);
            $smarty->assign('points', $points);
            $smarty->assign('points_sight', $pnts_sight);
            $smarty->assign('points_servo', $pnts_service);
            $smarty->assign('page_url', $this->basepath);

            $smarty->assign('types_select', $alltypes);

            $dbsc = $db->getTableName('statcity');
            $hash = $this->getUserHash();
            $db->sql = "INSERT INTO $dbsc (sc_citypage_id, sc_date, sc_hash) VALUES ('{$row['pc_id']}', now(), '$hash')
                        ON DUPLICATE KEY UPDATE sc_date = now()";
            $db->exec();

            $this->addTitle($row['pc_title'] . ': достопримечательности');
            if ($row['pc_description'])
                $this->addDescription($row['pc_description']);
            $this->addDescription('Достопримечательности ' . $row['pc_inwheretext'] . ' с GPS-координатами');
            if ($row['pc_keywords'])
                $this->addKeywords($row['pc_keywords']);
            $this->addKeywords('достопримечательности ' . $row['pc_inwheretext']);
            $this->addKeywords('Координаты GPS');
            $this->addKeywords($row['pc_title_translit']);
            if ($row['pc_title_english'] && $row['pc_title_english'] != $row['pc_title_translit'])
                $this->addKeywords($row['pc_title_english']);
            if ($row['pc_title_synonym'])
                $this->addKeywords($row['pc_title_synonym']);
            $this->isCounters = 1;
            $this->getCounters();

            if ($this->checkEdit())
                return $smarty->fetch(_DIR_TEMPLATES . '/_pages/pagecity.edit.sm.html');
            else
                return $smarty->fetch(_DIR_TEMPLATES . '/_pages/pagecity.show.sm.html');
        }
        else
            return FALSE;
    }

    private function getPageObject($db, $smarty, $id) {
        if (!$id)
            return false;
        $dbpt = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dbsp = $db->getTableName('statpoints');
        $dbrp = $db->getTableName('ref_pointtypes');
        $dburl = $db->getTableName('region_url');

        $uridata = explode('/', $_SERVER['REQUEST_URI']);
        array_pop($uridata);
        $url = implode('/', $uridata);

        $db->sql = "SELECT pt.pt_name, pt.pt_description, pt.pt_citypage_id,
                            pt.pt_latitude, pt.pt_longitude, pt_latlon_zoom,
                            pt.pt_website, pt.pt_adress, pt.pt_email, pt.pt_worktime, pt.pt_phone,
                            rp.tp_name, rp.tr_sight, rp.tp_short, rp.tp_icon,
                            UNIX_TIMESTAMP(pt.pt_lastup_date) AS last_update
                    FROM $dbpt AS pt
                    LEFT JOIN $dbrp AS rp ON pt.pt_type_id = rp.tp_id
                    WHERE pt.pt_id = '$id'
                    AND pt.pt_active = 1";
        $db->exec();
        $object = $db->fetch();
        if (!$object)
            return false;
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

        //--------------------  c a n o n i c a l  ------------------------
        $db->sql = "SELECT url FROM $dburl WHERE uid = '{$city['pc_url_id']}'";
        $db->exec();
        $canonical_url = $db->fetch();
        if ($canonical_url['url'] != '') {
            $this->canonical = $canonical_url['url'] . "/object$id.html";
            if ($canonical_url['url'] != $url) {
                //$this->getError('301', "$this->canonical");
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: $this->canonical");
            }
        }

        //------------------  s t a t i s t i c s  ------------------------
        $hash = $this->getUserHash();
        $db->sql = "INSERT INTO $dbsp (sp_pagepoint_id, sp_date, sp_hash) VALUES ('$id', now(), '$hash')
                        ON DUPLICATE KEY UPDATE sp_date = now()";
        $db->exec();

        $this->isCounters = 1;
        $this->getCounters();

        $this->addTitle($city['pc_title']);
        $this->addTitle($object['esc_name']);
        if ($object['tr_sight'])
            $this->addDescription('Достопримечательности ' . $city['pc_inwheretext']);
        if (isset($object['gps_dec']))
            $this->addDescription('GPS-координаты');
        $this->addDescription("{$object['tp_short']} {$city['pc_inwheretext']}");
        $this->addDescription($object['esc_name']);
        $this->addDescription($short);
        $this->addKeywords($city['pc_title']);
        $this->addKeywords($object['esc_name']);
        if (isset($object['gps_dec']))
            $this->addKeywords('координаты GPS');

        return $smarty->fetch(_DIR_TEMPLATES . '/_pages/pagepoint.sm.html');
    }

}

?>