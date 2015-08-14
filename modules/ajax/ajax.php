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
        } elseif ($page_id == 'blog') {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            if ($id == 'addform') {
                $this->content = $this->getFormBlog($smarty);
            } elseif ($id == 'editform' && intval($_GET['brid'])) {
                $this->content = $this->getFormBlog($smarty, intval($_GET['brid']));
            } elseif ($id == 'saveform') {
                $this->content = $this->saveFormBlog();
            } elseif ($id == 'delentry' && intval($_GET['bid'])) {
                $this->content = $this->deleteBlogEntry(intval($_GET['bid']));
            }
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 1, 2050);
        } elseif ($page_id == 'page') {
            if ($id == 'gps') {
                $this->content = $this->getTextPage($smarty, 31);
            } else {
                $this->getError('404');
            }
        } elseif ($page_id == 'YMapsML') {
            if ($id == 'getcitypoints' && isset($_GET['cid']) && intval($_GET['cid'])) {
                $this->content = $this->getCityPointsYMapsML($smarty, intval($_GET['cid']));
            } elseif ($id == 'getcitymap' && isset($_GET['cid']) && intval($_GET['cid'])) {
                $this->content = $this->getCityMapYMapsML($smarty, intval($_GET['cid']));
            } elseif ($id == 'getcommonmap') {
                $this->content = $this->getCommonMapYMapsML($smarty, $_GET);
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
        $db = $this->db;
        $dbm = $db->getTableName('modules');
        $db->sql = "SELECT md_pagecontent FROM $dbm WHERE md_id = '$pg_id'";
        $db->exec();
        $md = $db->fetch();
        return '<h3>Экспорт данных GPS</h3>' . $md['md_pagecontent'];
    }

//-------------------------------------------------------------- BLOG ----------
    private function deleteBlogEntry($bid) {
        if (!$this->checkEdit()) {
            return FALSE;
        }
        $brid = cut_trash_int($_POST['brid']);
        if (!$brid || !$bid || $brid != $bid) {
            return FALSE;
        }
        $db = $this->db;
        $dbb = $db->getTableName('blogentries');
        $db->sql = "DELETE FROM $dbb WHERE br_id = '$brid'";
        return $db->exec();
    }

    private function getFormBlog($smarty, $br_id = null) {
        if (!$this->checkEdit()) {
            return FALSE;
        }
        if ($br_id) {
            $db = $this->db;
            $dbb = $db->getTableName('blogentries');
            $db->sql = "SELECT br_id, br_date, br_title, br_text, br_active, br_url,
                        DATE_FORMAT(br_date, '%d.%m.%Y') as br_day,
                        DATE_FORMAT(br_date, '%H:%i') as br_time,
                        DATE_FORMAT(br_date,'%Y') as bg_year, DATE_FORMAT(br_date,'%m') as bg_month, DATE_FORMAT(br_date,'%d') as bg_day
                        FROM $dbb
                        WHERE br_id = '$br_id'
                        LIMIT 1";
            $db->exec();
            $entry = $db->fetch();
            $smarty->assign('entry', $entry);
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            return $smarty->fetch(_DIR_TEMPLATES . '/blog/ajax.editform.sm.html');
        } else {
            $entry = array(
                'br_day' => date('d.m.Y'), 'br_time' => date('H:i'),
                'bg_year' => date('Y'), 'bg_month' => date('m'), 'br_url' => date('d'));
            $smarty->assign('entry', $entry);
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            return $smarty->fetch(_DIR_TEMPLATES . '/blog/ajax.addform.sm.html');
        }
    }

    private function saveFormBlog($br_id = null) {
        if (!$this->checkEdit()) {
            return FALSE;
        }
        $brid = cut_trash_int($_POST['brid']);
        $ntitle = cut_trash_text($_POST['ntitle']);
        $ntext = cut_trash_html($_POST['ntext']);
        $ndate = transSQLdate(cut_trash_string($_POST['ndate']));
        $ntime = cut_trash_string($_POST['ntime']);
        $nact = cut_trash_string($_POST['nact']);
        $nact = ($nact == 'true') ? 1 : 0;
        $nurl = cut_trash_string($_POST['nurl']);
        $nuser = $this->getUserId();

        $db = $this->db;
        $dbb = $db->getTableName('blogentries');
        if ($_POST['brid'] == 'add') {
            $db->sql = "INSERT INTO $dbb SET
                        br_title='$ntitle', br_text='$ntext', br_date = '$ndate $ntime', br_active = '$nact', br_url='$nurl', br_us_id = '$nuser'";
        } elseif ($brid > 0) {
            $db->sql = "UPDATE $dbb SET
                        br_title='$ntitle', br_text='$ntext', br_date = '$ndate $ntime', br_active = '$nact', br_url='$nurl'
                        WHERE br_id = '$brid'";
        } else {
            return $this->getError('404');
        }

        return $db->exec();
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
        $db = $this->db;
        $dbpp = $db->getTableName('pagepoints');
        //print_x($_POST);
        $n_lat = cut_trash_string($_POST['pt_lat']);
        $n_lat = str_replace(',', '.', $n_lat);
        $n_lon = cut_trash_string($_POST['pt_lon']);
        $n_lon = str_replace(',', '.', $n_lon);
        $n_zoom = cut_trash_int($_POST['pt_zoom']);

        $db->sql = "UPDATE $dbpp SET pt_latitude = '$n_lat', pt_longitude = '$n_lon', pt_latlon_zoom = '$n_zoom', pt_lastup_date = now() WHERE pt_id = '$pid'";
        if ($db->exec()) {
            $point_lat_short = mb_substr($n_lat, 0, 8);
            $point_lon_short = mb_substr($n_lon, 0, 8);
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
        $db = $this->db;
        $dbpp = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dbrpt = $db->getTableName('ref_pointtypes');
        $db->sql = "SELECT pt.pt_name, pt.pt_id, pt_type_id, pt.pt_latitude, pt.pt_longitude, pt.pt_latlon_zoom, pt.pt_adress,
                    pc.pc_title, pc.pc_latitude, pc.pc_longitude,
                    rpt.tp_name, rpt.tp_icon
                    FROM $dbpp AS pt
                    LEFT JOIN $dbpc AS pc ON pt.pt_citypage_id = pc.pc_id
                    LEFT JOIN $dbrpt AS rpt ON rpt.tp_id = pt.pt_type_id
                    WHERE pt_id='$pid'";
        $db->exec();
        $point = $db->fetch();
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
        $db = $this->db;
        $dbpp = $db->getTableName('pagepoints');
        $dbpt = $db->getTableName('ref_pointtypes');
        $db->sql = "SELECT pt_id, pt_name, pt_type_id FROM $dbpp WHERE pt_id = '$point_id'";
        $db->exec();
        $point = $db->fetch();
        $smarty->assign('point', $point);
        $db->sql = "SELECT tp_id, tp_name, tp_icon FROM $dbpt ORDER BY tr_sight desc, tr_order";
        $db->exec();
        while ($row = $db->fetch()) {
            $row['current'] = ($row['tp_id'] == $point['pt_type_id']) ? 1 : 0;
            $types[] = $row;
        }
        $smarty->assign('alltypes', $types);
        return $smarty->fetch(_DIR_TEMPLATES . '/_ajax/changetype.form.sm.html');
    }

    private function setPointType($pid) {
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $ppid = cut_trash_int($_POST['pid']);
        $type = cut_trash_int($_POST['ntype']);
        if ($pid != $ppid || !$type) {
            return $this->getError('403');
        }
        $db = $this->db;
        $dbpp = $db->getTableName('pagepoints');
        $dbpt = $db->getTableName('ref_pointtypes');
        $db->sql = "UPDATE $dbpp SET pt_type_id = '$type', pt_lastup_date = now() WHERE pt_id = '$ppid'";
        $db->exec();
        $db->sql = "SELECT tp_icon FROM $dbpt WHERE tp_id = '$type'";
        $db->exec();
        $row = $db->fetch();
        return $row['tp_icon'];
    }

    private function getPointNew($id, $smarty) {
        if ($this->checkEdit()) {
            $db = $this->db;
            $city_title = '';
            if (isset($_GET['cid'])) {
                $cid = cut_trash_int($_GET['cid']);
                $dbpc = $db->getTableName('pagecity');
                $db->sql = "SELECT * FROM $dbpc WHERE pc_id = '$cid' LIMIT 1";
                $db->exec();
                $row = $db->fetch();
                $city_title = 'г. ' . $row['pc_title'];
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
        $ppid = cut_trash_int($_POST['pid']);
        if ($pid != $ppid) {
            return $this->getError('403');
        }
        $db = $this->db;
        $dbpp = $db->getTableName('pagepoints');
        $db->sql = "UPDATE $dbpp SET pt_active = 0, pt_lastup_date = now() WHERE pt_id = '$ppid'";
        if ($db->exec()) {
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
            'pt_website' => strlen(trim($_POST['nweb'])) > 0 ? 'http://' . str_replace('http://', '', trim($_POST['nweb'])) : null,
            'pt_email' => trim($_POST['nmail']),
            'pt_worktime' => trim($_POST['nwork']),
            'pt_adress' => trim($_POST['naddr']),
            'pt_phone' => trim($_POST['nphone']),
            'pt_is_best' => intval(!empty($_POST['nbest']) && $_POST['nbest'] == "checked"),
            'pt_rank' => 0,
        );
        if ($_POST['nlat'] != '' && $_POST['nlon'] != '') {
            $add_item['pt_latitude'] = floatval(str_replace(',', '.', trim($_POST['nlat'])));
            $add_item['pt_longitude'] = floatval(str_replace(',', '.', trim($_POST['nlon'])));
        }
        return $pts->insert($add_item);
    }

    private function savePointTitle($id, $smarty) {
        if (!$id) {
            return $this->getError('404');
        }
        $nname = cut_trash_string($_POST['nname']);
        $nid = cut_trash_int($_POST['id']);
        $uid = $this->getUserId();
        if ($id != $nid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $db = $this->db;
        $dbpp = $db->getTableName('pagepoints');
        $db->sql = "UPDATE $dbpp SET pt_name = '$nname', pt_lastup_user = '$uid', pt_lastup_date = now() WHERE pt_id = '$nid'";
        if ($db->exec()) {
            $db->sql = "SELECT pt_name FROM $dbpp WHERE pt_id = '$nid'";
            $db->exec();
            $row = $db->fetch();
            return $row['pt_name'];
        } else {
            return $this->getError('404');
        }
    }

    private function savePointDescr($id, $smarty) {
        if (!$id) {
            return $this->getError('404');
        }
        $ndesc = cut_trash_string($_POST['ndesc']);
        $nid = cut_trash_int($_POST['id']);
        $uid = $this->getUserId();
        if ($id != $nid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $db = $this->db;
        $dbpp = $db->getTableName('pagepoints');
        $dbvp = $db->getTableName('verpoints');
        $db->sql = "INSERT INTO $dbvp (vp_point_id, vp_date, vp_text, vp_hash, vp_userid) SELECT $nid, now(), pt_description, md5(pt_description),'$uid' FROM $dbpp WHERE pt_id = '$nid'";
        $db->exec();
        $db->sql = "UPDATE $dbpp SET pt_description = '$ndesc', pt_lastup_user = '$uid', pt_lastup_date = now() WHERE pt_id = '$nid'";
        if ($db->exec()) {
            $db->sql = "SELECT pt_description FROM $dbpp WHERE pt_id = '$nid'";
            $db->exec();
            $row = $db->fetch();
            return $row['pt_description'];
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

        $sp = new Statpoints($this->db);
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

        $sp = new Statpoints($this->db);
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
        $db = $this->db;
        $dbpc = $db->getTableName('pagecity');
        //print_x($_POST);
        $n_lat = cut_trash_string($_POST['pc_lat']);
        $n_lat = str_replace(',', '.', $n_lat);
        $n_lon = cut_trash_string($_POST['pc_lon']);
        $n_lon = str_replace(',', '.', $n_lon);
        $n_zoom = cut_trash_int($_POST['pc_zoom']);

        $db->sql = "UPDATE $dbpc SET pc_latitude = '$n_lat', pc_longitude = '$n_lon', pc_latlon_zoom = '$n_zoom', pc_lastup_date = now() WHERE pc_id = '$cid'";
        if ($db->exec()) {
            return TRUE;
        } else {
            return false;
        }
    }

    private function getFormCityGPS($cid, $smarty) {
        $db = $this->db;
        $dbpc = $db->getTableName('pagecity');
        $db->sql = "SELECT pc.pc_id, pc.pc_title, pc.pc_latitude, pc.pc_longitude, pc.pc_latlon_zoom
                    FROM $dbpc AS pc
                    WHERE pc.pc_id='$cid'";
        $db->exec();
        $city = $db->fetch();

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
        $ntitle = cut_trash_string($_POST['ntitle']);
        $nid = cut_trash_int($_POST['id']);
        if ($id != $nid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $db = $this->db;
        $dbpc = $db->getTableName('pagecity');
        $db->sql = "UPDATE $dbpc SET pc_title = '$ntitle', pc_lastup_date = now() WHERE pc_id = '$nid'";
        if ($db->exec()) {
            $db->sql = "SELECT pc_title FROM $dbpc WHERE pc_id = '$nid'";
            $db->exec();
            $row = $db->fetch();
            return $row['pc_title'];
        } else {
            return $this->getError('404');
        }
    }

    private function saveCityDescr($id, $smarty) {
        if (!$id) {
            return $this->getError('404');
        }
        $ntitle = cut_trash_string($_POST['ntext']);
        $nid = cut_trash_int($_POST['id']);
        if ($id != $nid) {
            return $this->getError('404');
        }
        if (!$this->checkEdit()) {
            return $this->getError('403');
        }
        $db = $this->db;
        $dbpc = $db->getTableName('pagecity');
        $dbvc = $db->getTableName('vercity');
        $db->sql = "INSERT INTO $dbvc (vc_cityid, vc_datecreate, vc_text, vc_hash, vc_userid) SELECT $nid, now(), pc_text, md5(pc_text),1 FROM $dbpc WHERE pc_id = '$nid'";
        $db->exec();
        $db->sql = "UPDATE $dbpc SET pc_text = '$ntitle', pc_lastup_date = now() WHERE pc_id = '$nid'";
        if ($db->exec()) {
            $db->sql = "SELECT pc_text FROM $dbpc WHERE pc_id = '$nid'";
            $db->exec();
            $row = $db->fetch();
            return $row['pc_text'];
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
        $points = array();
        while ($pt = $db->fetch()) {
            $points[] = $pt;
        }
        //print_x($points);
        $smarty->assign('points', $points);

        header("Content-type: application/xml");
        echo $smarty->fetch(_DIR_TEMPLATES . '/_XML/GPX.export.sm.xml');
        exit();
    }

    /**
     * Функция возвращает XML файл со всеми точками
     * Входные параметры:
     * массив get
     * опционально включает clt и cln - координаты центра
     * или
     */
    private function getCommonMapYMapsML($smarty, $get) {
        $db = $this->db;
        $dbpr = $db->getTableName('ref_pointtypes');
        $dbpp = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dbru = $db->getTableName('region_url');

        $ptypes = array();
        $bounds = array(
            'max_lat' => 55.9864578247, 'max_lon' => 37.9002265930,
            'min_lat' => 55.4144554138, 'min_lon' => 37.1716384888,
            'center_lat' => null, 'center_lon' => null,
            'delta_lat' => 0.1, 'delta_lon' => 0.3,
        );
        $points = array();

        $db->sql = "SELECT * FROM $dbpr";
        $db->exec();
        while ($rpt = $db->fetch()) {
            $ptypes[] = $rpt;
        }

        if (!isset($get['center']) && isset($get['clt']) && isset($get['cln']) && !isset($get['llt']) && !isset($get['lln']) && !isset($get['rlt']) && !isset($get['rln'])) {
            //---------- по координатам центра (раздельно)
            $bounds['center_lat'] = cut_trash_float($get['clt']);
            $bounds['center_lon'] = cut_trash_float($get['cln']);
            $bounds['max_lat'] = $bounds['center_lat'] + $bounds['delta_lat'];
            $bounds['max_lon'] = $bounds['center_lon'] + $bounds['delta_lon'];
            $bounds['min_lat'] = $bounds['center_lat'] - $bounds['delta_lat'];
            $bounds['min_lon'] = $bounds['center_lon'] - $bounds['delta_lon'];
        } elseif (isset($get['center']) && !isset($get['clt']) && !isset($get['cln']) && !isset($get['llt']) && !isset($get['lln']) && !isset($get['rlt']) && !isset($get['rln'])) {
            //---------- по координатам центра (в одном)
            $center = explode(',', $get['center']);
            $bounds['center_lat'] = cut_trash_float($center[1]);
            $bounds['center_lon'] = cut_trash_float($center[0]);
            $bounds['max_lat'] = $bounds['center_lat'] + $bounds['delta_lat'];
            $bounds['max_lon'] = $bounds['center_lon'] + $bounds['delta_lon'];
            $bounds['min_lat'] = $bounds['center_lat'] - $bounds['delta_lat'];
            $bounds['min_lon'] = $bounds['center_lon'] - $bounds['delta_lon'];
        } elseif (!isset($get['center']) && isset($get['llt']) && isset($get['lln']) && isset($get['rlt']) && isset($get['rln']) && !isset($get['clt']) && !isset($get['cln'])) {
            //---------- по координатам левого и правого угла
            $bounds['max_lat'] = cut_trash_float($get['rlt']);
            $bounds['max_lon'] = cut_trash_float($get['rln']);
            $bounds['min_lat'] = cut_trash_float($get['llt']);
            $bounds['min_lon'] = cut_trash_float($get['lln']);
            $bounds['delta_lat'] = $bounds['max_lat'] - $bounds['min_lat'];
            $bounds['delta_lon'] = $bounds['max_lon'] - $bounds['min_lon'];
            $bounds['center_lat'] = $bounds['min_lat'] + $bounds['delta_lat'];
            $bounds['center_lon'] = $bounds['min_lon'] + $bounds['delta_lon'];
        } else {
            //---------- по умолчанию берем Москву
            $bounds['delta_lat'] = $bounds['max_lat'] - $bounds['min_lat'];
            $bounds['delta_lon'] = $bounds['max_lon'] - $bounds['min_lon'];
            $bounds['center_lat'] = $bounds['min_lat'] + $bounds['delta_lat'];
            $bounds['center_lon'] = $bounds['min_lon'] + $bounds['delta_lon'];
        }

        $db->sql = "SELECT pp.*,
                        CONCAT('" . _URL_ROOT . "', ru.url, '/') AS cityurl,
                        CONCAT('" . _URL_ROOT . "', ru.url, '/object', pp.pt_id, '.html') AS objurl
                    FROM $dbpp AS pp
                    LEFT JOIN $dbpr pt ON pt.tp_id = pp.pt_type_id
                    LEFT JOIN $dbpc pc ON pc.pc_id = pp.pt_citypage_id
                    LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                    WHERE pp.pt_active = 1
                    AND pp.pt_latitude BETWEEN '{$bounds['min_lat']}' AND '{$bounds['max_lat']}'
                    AND pp.pt_longitude BETWEEN '{$bounds['min_lon']}' AND '{$bounds['max_lon']}'
                    ORDER BY pt.tr_order DESC, pp.pt_rank
                    LIMIT 200";
        $db->exec();
        $points = array();
        while ($pt = $db->fetch()) {
            $pt['pt_description'] = strip_tags($pt['pt_description']);
            $pt['pt_description'] = html_entity_decode($pt['pt_description'], ENT_QUOTES, 'UTF-8');
            $short_end = @mb_strpos($pt['pt_description'], ' ', 50, 'utf-8');
            $pt['pt_short'] = trim(mb_substr($pt['pt_description'], 0, $short_end, 'utf-8'), "\x00..\x1F,.-");
            $pt['pt_website'] = htmlspecialchars($pt['pt_website'], ENT_QUOTES);
            $points[] = $pt;
        }

        $smarty->assign('ptypes', $ptypes);
        $smarty->assign('bounds', $bounds);
        $smarty->assign('points', $points);

        header("Content-type: application/xml");
        echo $smarty->fetch(_DIR_TEMPLATES . '/_XML/YMapsML3.sm.xml');
        exit();
    }

    private function getCityMapYMapsML($smarty, $cid) {
        if (!$cid)
            $this->getError('404');
        $db = $this->db;
        $dbpr = $db->getTableName('ref_pointtypes');
        $dbpp = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dbru = $db->getTableName('region_url');

        $db->sql = "SELECT * FROM $dbpr";
        $db->exec();
        $ptypes = array();
        while ($rpt = $db->fetch()) {
            $ptypes[] = $rpt;
        }

        $db->sql = "SELECT CONCAT('https://', '" . _URL_ROOT . "', ru.url, '/') AS url
                    FROM $dbpc pc
                    LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                    WHERE pc.pc_id = '$cid'
                    LIMIT 1";
        $db->exec();
        $canonical_link = $db->fetch();

        $db->sql = "SELECT MAX(pt_latitude) AS max_lat, MIN(pt_latitude) AS min_lat, MAX(pt_longitude) AS max_lon, MIN(pt_longitude) AS min_lon
                    FROM $dbpp
                    WHERE pt_citypage_id='$cid'
                    AND pt_latitude != ''
                    AND pt_longitude != ''";
        $db->exec();
        $bounds = $db->fetch();

        $db->sql = "SELECT pp.*
                    FROM $dbpp AS pp
                    LEFT JOIN $dbpr pt ON pt.tp_id = pp.pt_type_id
                    WHERE pp.pt_citypage_id = '$cid'
                    AND pp.pt_latitude != ''
                    AND pp.pt_longitude != ''
                    AND pp.pt_active = 1
                    ORDER BY pt.tr_order DESC, pp.pt_rank";
        $db->exec();
        $points = array();
        while ($pt = $db->fetch()) {
            $pt['pt_description'] = strip_tags($pt['pt_description']);
            $pt['pt_description'] = html_entity_decode($pt['pt_description'], ENT_QUOTES, 'UTF-8');
            $short_end = @mb_strpos($pt['pt_description'], ' ', 50, 'utf-8');
            $pt['pt_short'] = trim(mb_substr($pt['pt_description'], 0, $short_end, 'utf-8'), "\x00..\x1F,.-");
            $pt['pt_website'] = htmlspecialchars($pt['pt_website'], ENT_QUOTES);
            $points[] = $pt;
        }

        $db->sql = "SELECT pc2.pc_id, pc2.pc_title, pc2.pc_latitude, pc2.pc_longitude, CONCAT(ru.url, '/') AS url
                    FROM $dbpc pc
                    LEFT JOIN $dbpc pc2 ON pc2.pc_region_id = pc.pc_region_id AND pc2.pc_id != pc.pc_id
                    LEFT JOIN $dbru ru ON ru.uid = pc2.pc_url_id
                    WHERE pc.pc_id = '$cid'
                    AND pc2.pc_city_id != 0";
        $db->exec();
        $city = array();
        while ($pc = $db->fetch()) {
            $city[] = $pc;
        }

        $smarty->assign('ptypes', $ptypes);
        $smarty->assign('bounds', $bounds);
        $smarty->assign('canonical_link', $canonical_link);
        $smarty->assign('points', $points);
        $smarty->assign('city', $city);

        header("Content-type: application/xml");
        echo $smarty->fetch(_DIR_TEMPLATES . '/_XML/YMapsML2.sm.xml');
        exit();
    }

    private function getCityPointsYMapsML($smarty, $cid) {
        if (!$cid) {
            $this->getError('404');
        }
        $db = $this->db;
        $dbpr = $db->getTableName('ref_pointtypes');
        $dbpp = $db->getTableName('pagepoints');
        $dbpc = $db->getTableName('pagecity');
        $dbru = $db->getTableName('region_url');
        $db->sql = "SELECT * FROM $dbpr";
        $db->exec();
        $ptypes = array();
        while ($rpt = $db->fetch()) {
            $ptypes[] = $rpt;
        }
        $smarty->assign('ptypes', $ptypes);

        $db->sql = "SELECT pp.*,
                        CONCAT('" . _URL_ROOT . "', ru.url, '/') AS cityurl,
                        CONCAT('" . _URL_ROOT . "', ru.url, '/object', pp.pt_id, '.html') AS objurl
                    FROM $dbpp AS pp
                    LEFT JOIN $dbpc pc ON pc.pc_id = pp.pt_citypage_id
                    LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                    WHERE pt_citypage_id='$cid'
                    AND pt_latitude != ''
                    AND pt_longitude != ''
                    AND pt_active = 1";
        $db->exec();
        $points = array();
        while ($pt = $db->fetch()) {
            $pt['pt_description'] = strip_tags($pt['pt_description']);
            $pt['pt_description'] = html_entity_decode($pt['pt_description'], ENT_QUOTES, 'UTF-8');
            $short_end = @mb_strpos($pt['pt_description'], ' ', 100, 'utf-8');
            $pt['pt_short'] = trim(mb_substr($pt['pt_description'], 0, $short_end, 'utf-8'), "\x00..\x1F,.-");
            $pt['pt_website'] = htmlspecialchars($pt['pt_website'], ENT_QUOTES);
            $points[] = $pt;
        }

        $db->sql = "SELECT pc2.pc_id, pc2.pc_title, pc2.pc_latitude, pc2.pc_longitude, CONCAT(ru.url, '/') AS url
                    FROM $dbpc pc
                        LEFT JOIN $dbpc pc2 ON pc2.pc_region_id = pc.pc_region_id AND pc2.pc_id != pc.pc_id
                            LEFT JOIN $dbru ru ON ru.uid = pc2.pc_url_id
                    WHERE pc.pc_id = '$cid'
                        AND pc2.pc_city_id != 0";
        $db->exec();
        $city = array();
        while ($pc = $db->fetch()) {
            $city[] = $pc;
        }

        $db->sql = "SELECT pc.*
                    FROM $dbpc pc
                    WHERE pc.pc_id = '$cid'";
        $db->exec();
        $this_city = $db->fetch();

        if ($this_city['pc_region_id'] == 0) {
            $db->sql = "SELECT pc2.pc_id, pc2.pc_title, pc2.pc_latitude, pc2.pc_longitude, CONCAT(ru.url, '/') AS url
                        FROM $dbpc pc2
                            LEFT JOIN $dbru ru ON ru.uid = pc2.pc_url_id
                        WHERE pc2.pc_country_id = '{$this_city['pc_country_id']}'
                            AND pc2.pc_city_id != 0";
            $db->exec();
            while ($pc = $db->fetch()) {
                $city[] = $pc;
            }
        }

        $smarty->assign('points', $points);
        $smarty->assign('city', $city);
        header("Content-type: application/xml");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Expires: " . date("r"));
        echo $smarty->fetch(_DIR_TEMPLATES . '/_XML/YMapsML1.sm.xml');
        exit();
    }

}
