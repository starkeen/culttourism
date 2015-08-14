<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        global $smarty;
        $smarty->caching = false;
        parent::__construct($db, 'ajax');
        $id = urldecode($id);
        if (strpos($id, '?') !== FALSE) {
            $id = substr($id, 0, strpos($id, '?'));
        }
        $this->id = $id;
        $this->auth->setService('ajax');

        if ($page_id == 'forms' && $id == 'commonlogin') {
            $this->content = $this->getFormLogin($smarty);
        } elseif ($page_id == 'point') {
            if ($id == '' && isset($_GET['id']) && intval($_GET['id'])) {
                $this->content = $this->getPoint(intval($_GET['id']));
            } elseif ($id == 's' && isset($_GET['id'])) {
                $this->content = $this->getPointBySlugline(str_replace('.html', '', $_GET['id']));
            } elseif ($id == 'savetitle' && isset($_GET['id']) && intval($_GET['id'])) {
                $this->content = $this->savePointTitle(intval($_GET['id']), $smarty);
            } elseif ($id == 'savedescr' && isset($_GET['id']) && intval($_GET['id'])) {
                $this->content = $this->savePointDescr(intval($_GET['id']), $smarty);
            } elseif ($id == 'savecontacts' && isset($_GET['cid']) && intval($_GET['cid'])) {
                $this->content = $this->savePointContacts(intval($_GET['cid']), $smarty);
            } elseif ($id == 'getnewform') {
                $this->content = $this->getPointNew(intval($_GET['cid']), $smarty);
            } elseif ($id == 'savenew') {
                $this->content = $this->savePointNew(intval($_GET['cid']), $smarty);
            } elseif ($id == 'delpoint') {
                $this->content = $this->deletePoint(intval($_GET['pid']), $smarty);
            } elseif ($id == 'getformGPS') {
                $this->content = $this->getFormPointGPS(intval($_GET['pid']), $smarty);
            } elseif ($id == 'saveformGPS') {
                $this->content = $this->setFormPointGPS(intval($_GET['pid']));
            } elseif ($id == 'saveAddrGPS') {
                $this->content = $this->setFormPointAddr(intval($_GET['pid']));
            } elseif ($id == 'savebest') {
                $this->content = $this->setFormPointBest(intval($_GET['id']));
            }
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 1, 2050);
        } elseif ($page_id == 'city') {
            if ($id == 'savetitle' && isset($_GET['id']) && intval($_GET['id'])) {
                $this->content = $this->saveCityTitle(intval($_GET['id']), $smarty);
            } elseif ($id == 'savedescr' && isset($_GET['id']) && intval($_GET['id'])) {
                $this->content = $this->saveCityDescr(intval($_GET['id']), $smarty);
            } elseif ($id == 'getformGPS' && isset($_GET['cid']) && intval($_GET['cid'])) {
                $this->content = $this->getFormCityGPS(intval($_GET['cid']), $smarty);
            } elseif ($id == 'saveformGPS') {
                $this->content = $this->setFormCityGPS(intval($_GET['cid']), $smarty);
            }
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 1, 2050);
        } elseif ($page_id == 'pointtype') {
            if ($id == 'getform') {
                $this->content = $this->getChangeTypeForm($smarty);
            } elseif ($id == 'savetype' && isset($_POST['pid']) && intval($_POST['pid'])) {
                $this->content = $this->setPointType(intval($_POST['pid']));
            }
        } elseif ($page_id == 'page') {
            if ($id == 'gps') {
                $this->content = $this->getTextPage($smarty, 31);
            } else {
                $this->getError('404');
            }
        } elseif ($page_id == 'GPX' && $id == 'getcitypoints' && isset($_GET['cid']) && intval($_GET['cid'])) {
            $this->content = $this->getCityPointsGPX($smarty, intval($_GET['cid']));
        } else {
            $this->getError('404');
        }
    }

    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

//--------------------------------------------------------- TEXT PAGES ---------
    private function getTextPage($smarty, $pg_id) {
        $mds = new MModules($this->db);
        $md = $mds->getItemByPk($pg_id);
        return '<h3>Экспорт данных GPS</h3>' . $md['md_pagecontent'];
    }

//-------------------------------------------------------------- POINTS ----------
    private function savePointContacts($cid, $smarty) {
        if (!$cid) {
            return $this->getError('404');
        }
        $pp = new MPagePoints($this->db);

        $nid = intval($_POST['cid']);
        if ($cid != $nid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $out = $pp->updateByPk($cid, array(
            'pt_lastup_user' => $this->getUserId(),
            'pt_lastup_date' => $pp->now(),
            'pt_website' => $_POST['nwebsite'],
            'pt_email' => $_POST['nemail'],
            'pt_phone' => $_POST['nphone'],
            'pt_worktime' => $_POST['nworktime'],
            'pt_adress' => $_POST['nadress'],
        ));
        if ($out) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    private function setFormPointAddr($pid) {
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $p = new MPagePoints($this->db);
        return $p->updateByPk($pid, array('pt_adress' => $_POST['addr']));
    }

    private function setFormPointBest($pid) {
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $state = cut_trash_int(!empty($_POST['nstate']) && $_POST['nstate'] == "checked");
        $p = new MPagePoints($this->db);
        return $p->updateByPk($pid, array('pt_is_best' => $state));
    }

    private function setFormPointGPS($pid) {
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }

        $p = new MPagePoints($this->db);
        $state = $p->updateByPk($pid, array(
            'pt_latitude' => $_POST['pt_lat'],
            'pt_longitude' => $_POST['pt_lon'],
            'pt_latlon_zoom' => intval($_POST['pt_zoom']),
        ));

        if ($state) {
            $point_lat_short = mb_substr($_POST['pt_lat'], 0, 8);
            $point_lon_short = mb_substr($_POST['pt_lon'], 0, 8);
            if ($point_lat_short >= 0) {
                $point_lat_w = "N$point_lat_short";
            } else {
                $point_lat_w = "S$point_lat_short";
            }
            if ($point_lon_short >= 0) {
                $point_lon_w = "E$point_lon_short";
            } else {
                $point_lon_w = "W$point_lon_short";
            }
            return "$point_lat_w $point_lon_w";
        } else {
            return false;
        }
    }

    private function getFormPointGPS($pid, $smarty) {
        $dbpp = $this->db->getTableName('pagepoints');
        $dbpc = $this->db->getTableName('pagecity');
        $dbrpt = $this->db->getTableName('ref_pointtypes');
        $this->db->sql = "SELECT pt.pt_name, pt.pt_id, pt_type_id, pt.pt_latitude, pt.pt_longitude, pt.pt_latlon_zoom, pt.pt_adress,
                    pc.pc_title, pc.pc_latitude, pc.pc_longitude,
                    rpt.tp_name, rpt.tp_icon
                    FROM $dbpp AS pt
                    LEFT JOIN $dbpc AS pc ON pt.pt_citypage_id = pc.pc_id
                    LEFT JOIN $dbrpt AS rpt ON rpt.tp_id = pt.pt_type_id
                    WHERE pt_id='$pid'";
        $this->db->exec();
        $point = $this->db->fetch();
        //print_x($point);
        if ($point['pt_latitude'] && $point['pt_longitude']) {
            $point['map_center']['lat'] = $point['pt_latitude'];
            $point['map_center']['lon'] = $point['pt_longitude'];
            $point['map_point'] = 1;
        } elseif ($point['pc_latitude'] && $point['pc_longitude']) {
            $point['map_center']['lat'] = $point['pc_latitude'];
            $point['map_center']['lon'] = $point['pc_longitude'];
            $point['map_point'] = 0;
        } else {
            $point['map_center']['lat'] = 55.7557;
            $point['map_center']['lon'] = 37.6176;
            $point['map_point'] = -1;
        }
        if (!$point['tp_icon']) {
            $point['tp_name'] = 'другое';
            $point['tp_icon'] = 'star.png';
        }
        $point['zoom'] = ($point['pt_latlon_zoom'] != 0) ? $point['pt_latlon_zoom'] : 13;
        $smarty->assign('point', $point);
        return $smarty->fetch(_DIR_TEMPLATES . '/_ajax/changelatlon.form.sm.html');
    }

    private function getChangeTypeForm($smarty) {
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $point_id = cut_trash_int($_GET['pid']);
        if (!$point_id) {
            return $this->getError('403');
        }
        $p = new MPagePoints($this->db);
        $pts = new MRefPointtypes($this->db);

        $point = $p->getItemByPk(intval($_GET['pid']));
        $types = $pts->getActive();
        foreach ($types as $i => $type) {
            $types[$i]['current'] = ($type['tp_id'] == $point['pt_type_id']) ? 1 : 0;
        }

        $smarty->assign('point', $point);
        $smarty->assign('alltypes', $types);
        return $smarty->fetch(_DIR_TEMPLATES . '/_ajax/changetype.form.sm.html');
    }

    private function setPointType($pid) {
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $ppid = intval($_POST['pid']);
        $type = intval($_POST['ntype']);
        if ($pid != $ppid || !$type) {
            return $this->getError('403');
        }

        $p = new MPagePoints($this->db);
        $pts = new MRefPointtypes($this->db);

        $state = $p->updateByPk($pid, array(
            'pt_type_id' => $type,
        ));
        $newtype = $pts->getItemByPk($type);
        return $newtype['tp_icon'];
    }

    private function getPointNew($id, $smarty) {
        if ($this->checkEdit()) {
            $city_title = '';
            if (isset($_GET['cid'])) {
                $pc = new MPageCities($this->db);
                $city = $pc->getItemByPk(intval($_GET['cid']));
                $city_title = 'г. ' . $city['pc_title'];
            } else {
                $city_title = '';
            }
            $smarty->assign('city_title', $city_title);
            return $smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.add.sm.html');
        } else {
            return $this->getError('403');
        }
    }

    private function deletePoint($pid, $smarty) {
        if (!$pid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $ppid = intval($_POST['pid']);
        if ($pid != $ppid) {
            return $this->getError('403');
        }
        $pp = new MPagePoints($this->db);
        $state = $pp->deleteByPk($ppid);
        if ($state) {
            return $ppid;
        } else {
            return FALSE;
        }
    }

    private function savePointNew($cid, $smarty) {
        if (!$cid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        //print_x($_POST);

        $pts = new MPagePoints($this->db);
        $add_item = array(
            'pt_name' => trim($_POST['nname']) != '' ? trim($_POST['nname']) : '[не указано]',
            'pt_description' => trim($_POST['ndesc']),
            'pt_citypage_id' => intval($_POST['cid']),
            'pt_website' => trim($_POST['nweb']),
            'pt_email' => trim($_POST['nmail']),
            'pt_worktime' => trim($_POST['nwork']),
            'pt_adress' => trim($_POST['naddr']),
            'pt_phone' => trim($_POST['nphone']),
            'pt_is_best' => intval(!empty($_POST['nbest']) && $_POST['nbest'] == "checked"),
            'pt_rank' => 0,
        );
        if ($_POST['nlat'] != '' && $_POST['nlon'] != '') {
            $add_item['pt_latitude'] = trim($_POST['nlat']);
            $add_item['pt_longitude'] = trim($_POST['nlon']);
        }
        return $pts->insert($add_item);
    }

    private function savePointTitle($id, $smarty) {
        if (!$id) {
            return $this->getError('404');
        }
        $nid = intval($_POST['id']);
        if ($id != $nid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $pp = new MPagePoints($this->db);
        $state = $pp->updateByPk($nid, array(
            'pt_name' => $_POST['nname'],
            'pt_lastup_user' => $this->getUserId(),
        ));
        if ($state) {
            $point = $pp->getItemByPk($nid);
            return $point['pt_name'];
        } else {
            return $this->getError('404');
        }
    }

    private function savePointDescr($id, $smarty) {
        if (!$id) {
            return $this->getError('404');
        }
        $nid = intval($_POST['id']);
        if ($id != $nid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $pp = new MPagePoints($this->db);
        $state = $pp->updateByPk($nid, array(
            'pt_description' => $_POST['ndesc'],
            'pt_lastup_user' => $this->getUserId(),
        ));
        if ($state) {
            $point = $pp->getItemByPk($nid);
            return $point['pt_description'];
        } else {
            return $this->getError('404');
        }
    }

    private function getPoint($id) {
        if (!$id) {
            return $this->getError('404');
        }

        $pts = new MPagePoints($this->db);
        $object = $pts->getItemByPk($id);

        if (!$object) {
            return false;
        }

        $object['page_link'] = $object['url_canonical'];
        $object['gps_dec'] = '';

        $sp = new MStatpoints($this->db);
        $sp->add($object['pt_id'], $this->getUserHash());

        $this->smarty->assign('object', $object);

        if ($this->checkEdit()) {
            return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.edit.sm.html');
        } else {
            return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.show.sm.html');
        }
    }

    private function getPointBySlugline($slugline) {
        if (!$slugline) {
            return $this->getError('404');
        }

        $pts = new MPagePoints($this->db);
        $objects = $pts->searchSlugline($slugline);
        $object = isset($objects[0]) ? $objects[0] : false;
        if (!$object) {
            return false;
        }

        $object['page_link'] = $object['url_canonical'];

        $sp = new MStatpoints($this->db);
        $sp->add($object['pt_id'], $this->getUserHash());

        $this->smarty->assign('object', $object);

        if ($this->checkEdit()) {
            return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.edit.sm.html');
        } else {
            return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.show.sm.html');
        }
    }

//-------------------------------------------------------------- CITY ----------
    private function setFormCityGPS($cid, $smarty) {
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $pc = new MPageCities($this->db);
        $state = $pc->updateByPk($cid, array(
            'pc_latitude' => $_POST['pc_lat'],
            'pc_longitude' => $_POST['pc_lon'],
            'pc_latlon_zoom' => $_POST['pc_zoom'],
        ));

        if ($state) {
            return true;
        } else {
            return false;
        }
    }

    private function getFormCityGPS($cid, $smarty) {
        $pc = new MPageCities($this->db);
        $city = $pc->getItemByPk($cid);

        if ($city['pc_latitude'] && $city['pc_longitude']) {
            $city['map_center']['lat'] = $city['pc_latitude'];
            $city['map_center']['lon'] = $city['pc_longitude'];
            $city['zoom'] = ($city['pc_latlon_zoom']) ? $city['pc_latlon_zoom'] : 13;
            $city['map_point'] = 1;
        } else {
            $city['map_center']['lat'] = 55.7557;
            $city['map_center']['lon'] = 37.6176;
            $city['zoom'] = 3;
            $city['map_point'] = -1;
        }

        $smarty->assign('city', $city);
        return $smarty->fetch(_DIR_TEMPLATES . '/_ajax/citylatlon.form.sm.html');
    }

    private function saveCityTitle($id, $smarty) {
        if (!$id) {
            return $this->getError('404');
        }
        $nid = intval($_POST['id']);
        if ($id != $nid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $pc = new MPageCities($this->db);
        $state = $pc->updateByPk($nid, array(
            'pc_title' => $_POST['ntitle'],
        ));
        if ($state) {
            $city = $pc->getItemByPk($nid);
            return $city['pc_title'];
        } else {
            return $this->getError('404');
        }
    }

    private function saveCityDescr($id, $smarty) {
        if (!$id) {
            return $this->getError('404');
        }
        $nid = intval($_POST['id']);
        if ($id != $nid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }

        $pc = new MPageCities($this->db);
        $state = $pc->updateByPk($nid, array(
            'pc_text' => $_POST['ntext'],
        ));
        if ($state) {
            $city = $pc->getItemByPk($nid);
            return $city['pc_text'];
        } else {
            return $this->getError('404');
        }
    }

//-------------------------------------------------------------- SIGN ----------
    private function getFormLogin($smarty) {
        if (isset($_SESSION['user_id'])) {
            $smarty->assign('username', $_SESSION['user_name']);
            return $smarty->fetch(_DIR_TEMPLATES . '/sign/authuser.sm.html');
        } else {
            $smarty->assign('baseurl', _SITE_URL);
            $smarty->assign('authkey', 'ewtheqryb35yqb356y4ery');
            return $smarty->fetch(_DIR_TEMPLATES . '/sign/authform.sm.html');
        }
    }

//-------------------------------------------------------------- GPS ----------
    private function getCityPointsGPX($smarty, $cid) {
        if (!$cid) {
            $this->getError('404');
        }
        $db = $this->db;
        $dbpp = $db->getTableName('pagepoints');
        $db->sql = "SELECT pt_name, pt_id, pt_latitude, pt_longitude,
                    DATE_FORMAT(pt_lastup_date, '%Y-%m-%dT%H:%i:%sZ') as pt_date
                    FROM $dbpp
                    WHERE pt_citypage_id='$cid'
                    AND pt_latitude != ''
                    AND pt_longitude != ''";
        $db->exec();
        $points = $db->fetchAll();
        //print_x($points);
        $smarty->assign('points', $points);

        header("Content-type: application/xml");
        echo $smarty->fetch(_DIR_TEMPLATES . '/_XML/GPX.export.sm.xml');
        exit();
    }

}
