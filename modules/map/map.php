<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        global $smarty;
        parent::__construct($db, 'map', $page_id);
        $id = urldecode($id);
        if (strpos($id, '?') !== FALSE) {
            $id = substr($id, 0, strpos($id, '?'));
        }
        $this->id = $id;
        
        $this->mainfile_js = _ER_REPORT ? ('../sys/static/?type=js&pack=' . $module_id) : $this->globalsettings['res_js_' . $module_id];

        //========================  I N D E X  ================================
        if ($page_id == '') {
            $this->ymaps_ver = 2;
            //$smarty->assign('gps_text', $this->content);
            $this->content = $smarty->fetch(_DIR_TEMPLATES . '/map/map.sm.html');
            return true;
        }
        //====================  M A P   E N T R Y  ============================
        elseif ($page_id == 'common') {
            $this->auth->setService('map');
            $this->isAjax = true;
            $this->getYMapsMLCommon($_GET);
        } elseif ($page_id == 'city' && isset($_GET['cid']) && intval($_GET['cid']) > 0) {
            $this->auth->setService('map');
            $this->isAjax = true;
            $this->getYMapsMLRegion(intval($_GET['cid']));
        } elseif ($page_id == 'list' && isset($_GET['lid']) && intval($_GET['lid']) > 0) {
            $this->auth->setService('map');
            $this->isAjax = true;
            $this->getYMapsMLList(intval($_GET['lid']));
        }
        //==========================  E X I T  ================================
        else {
            $this->getError('404');
        }
    }

    private function getYMapsMLList($list_id) {
        $dbli = $this->db->getTableName('lists_items');
        $dbpp = $this->db->getTableName('pagepoints');
        $dbpc = $this->db->getTableName('pagecity');
        $dbru = $this->db->getTableName('region_url');
        $dbpr = $this->db->getTableName('ref_pointtypes');

        $points = array();
        $bounds = array(
            'max_lat' => 0, 'max_lon' => 0,
            'min_lat' => 180, 'min_lon' => 180,
            'center_lat' => null, 'center_lon' => null,
            'delta_lat' => 0.1, 'delta_lon' => 0.3,
        );

        $ptypes = $this->getRefPointTypes();

        $this->db->sql = "SELECT pp.*,
                                0 AS obj_selected,
                                CONCAT(:url_root1, ru.url, '/') AS cityurl,
                                CONCAT(:url_root2, ru.url, '/', pp.pt_slugline, '.html') AS objurl,
                                CONCAT(ru.url, '/', pp.pt_slugline, '.html') AS objuri
                            FROM $dbli li
                                LEFT JOIN $dbpp AS pp ON pp.pt_id = li.li_pt_id
                                    LEFT JOIN $dbpr pt ON pt.tp_id = pp.pt_type_id
                                    LEFT JOIN $dbpc pc ON pc.pc_id = pp.pt_citypage_id
                                        LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                            WHERE pp.pt_active = 1
                                AND pt_latitude != 0
                                AND pt_longitude != 0
                                AND li.li_active
                                AND li.li_ls_id = :list_id
                            ORDER BY pt.tr_order DESC, pp.pt_rank
                            LIMIT 300";
        
        $this->db->execute(array(
            ':list_id' => $list_id,
            ':url_root1' => _URL_ROOT,
            ':url_root2' => _URL_ROOT,
        ));
        //$this->db->showSQL();
        while ($pt = $this->db->fetch()) {
            $pt['pt_description'] = strip_tags($pt['pt_description']);
            $pt['pt_description'] = html_entity_decode($pt['pt_description'], ENT_QUOTES, 'UTF-8');
            $short_end = @mb_strpos($pt['pt_description'], ' ', 50, 'utf-8');
            $pt['pt_short'] = trim(mb_substr($pt['pt_description'], 0, $short_end, 'utf-8'), "\x00..\x1F,.-");
            $pt['pt_website'] = htmlspecialchars($pt['pt_website'], ENT_QUOTES);
            $points[] = $pt;

            if ($pt['pt_latitude'] > $bounds['max_lat']) {
                $bounds['max_lat'] = $pt['pt_latitude'];
            }
            if ($pt['pt_longitude'] > $bounds['max_lon']) {
                $bounds['max_lon'] = $pt['pt_longitude'];
            }
            if ($pt['pt_latitude'] < $bounds['min_lat']) {
                $bounds['min_lat'] = $pt['pt_latitude'];
            }
            if ($pt['pt_longitude'] < $bounds['min_lon']) {
                $bounds['min_lon'] = $pt['pt_longitude'];
            }
            $bounds['min_lat'] = $bounds['min_lat'] - $bounds['delta_lat'];
            $bounds['max_lat'] = $bounds['max_lat'] + $bounds['delta_lat'];
            $bounds['min_lon'] = $bounds['min_lon'] - $bounds['delta_lon'];
            $bounds['max_lon'] = $bounds['max_lon'] + $bounds['delta_lon'];
        }

        $this->smarty->assign('ptypes', $ptypes);
        $this->smarty->assign('bounds', $bounds);
        $this->smarty->assign('points', $points);

        header("Content-type: application/xml");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Expires: " . date("r"));
        echo $this->smarty->fetch(_DIR_TEMPLATES . '/_XML/YMapsML3.sm.xml');
        exit();
    }

    private function getYMapsMLRegion($cid) {
        if (!$cid) {
            $this->getError('404');
        }

        $dbpp = $this->db->getTableName('pagepoints');
        $dbpc = $this->db->getTableName('pagecity');
        $dbru = $this->db->getTableName('region_url');

        $ptypes = $this->getRefPointTypes();

        $this->db->sql = "SELECT pp.*,
                        CONCAT(:url_root1, ru.url, '/') AS cityurl,
                        CONCAT(:url_root2, ru.url, '/', pp.pt_slugline, '.html') AS objurl
                    FROM $dbpp AS pp
                    LEFT JOIN $dbpc pc ON pc.pc_id = pp.pt_citypage_id
                    LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                    WHERE pt_citypage_id = :cid
                    AND pt_latitude != ''
                    AND pt_longitude != ''
                    AND pt_active = 1";
        
        $this->db->execute(array(
            ':cid' => $cid,
            ':url_root1' => _URL_ROOT,
            ':url_root2' => _URL_ROOT,
        ));
        $points = array();
        while ($pt = $this->db->fetch()) {
            $pt['pt_description'] = strip_tags($pt['pt_description']);
            $pt['pt_description'] = html_entity_decode($pt['pt_description'], ENT_QUOTES, 'UTF-8');
            $short_end = @mb_strpos($pt['pt_description'], ' ', 100, 'utf-8');
            $pt['pt_short'] = trim(mb_substr($pt['pt_description'], 0, $short_end, 'utf-8'), "\x00..\x1F,.-");
            $pt['pt_website'] = htmlspecialchars($pt['pt_website'], ENT_QUOTES);
            $points[] = $pt;
        }

        $this->db->sql = "SELECT pc2.pc_id, pc2.pc_title, pc2.pc_latitude, pc2.pc_longitude, CONCAT(ru.url, '/') AS url
                    FROM $dbpc pc
                        LEFT JOIN $dbpc pc2 ON pc2.pc_region_id = pc.pc_region_id AND pc2.pc_id != pc.pc_id
                            LEFT JOIN $dbru ru ON ru.uid = pc2.pc_url_id
                    WHERE pc.pc_id = :cid
                        AND pc2.pc_city_id != 0";
        
        $this->db->execute(array(
            ':cid' => $cid,
        ));
        $city = array();
        while ($pc = $this->db->fetch()) {
            $city[] = $pc;
        }

        $this->db->sql = "SELECT pc.*
                    FROM $dbpc pc
                    WHERE pc.pc_id = :cid";
        
        $this->db->execute(array(
            ':cid' => $cid,
        ));
        $this_city = $this->db->fetch();

        if ($this_city['pc_region_id'] == 0) {
            $this->db->sql = "SELECT pc2.pc_id, pc2.pc_title, pc2.pc_latitude, pc2.pc_longitude, CONCAT(ru.url, '/') AS url
                        FROM $dbpc pc2
                            LEFT JOIN $dbru ru ON ru.uid = pc2.pc_url_id
                        WHERE pc2.pc_country_id = :pc_country_id
                            AND pc2.pc_city_id != 0";
            
            $this->db->execute(array(
                ':pc_country_id' => $this_city['pc_country_id'],
            ));
            while ($pc = $this->db->fetch()) {
                $city[] = $pc;
            }
        }

        $this->smarty->assign('ptypes', $ptypes);
        $this->smarty->assign('points', $points);
        $this->smarty->assign('city', $city);

        header("Content-type: application/xml");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Expires: " . date("r"));

        echo $this->smarty->fetch(_DIR_TEMPLATES . '/_XML/YMapsML1.sm.xml');
        exit();
    }

    private function getYMapsMLCommon($get) {
        $dbpp = $this->db->getTableName('pagepoints');
        $dbpc = $this->db->getTableName('pagecity');
        $dbru = $this->db->getTableName('region_url');
        $dbpr = $this->db->getTableName('ref_pointtypes');

        $ptypes = $this->getRefPointTypes();
        $bounds = array(
            'max_lat' => 55.9864578247, 'max_lon' => 37.9002265930,
            'min_lat' => 55.4144554138, 'min_lon' => 37.1716384888,
            'center_lat' => null, 'center_lon' => null,
            'delta_lat' => 0.1, 'delta_lon' => 0.3,
        );
        $points = array();

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
        if (isset($get['oid']) && intval($get['oid']) > 0) {
            $selected_object_id = intval($get['oid']);
        } else {
            $selected_object_id = 0;
        }

        $this->db->sql = "SELECT pp.*,
                                IF (pp.pt_id = :selected_object_id2, 1, 0) AS obj_selected,
                                CONCAT(:url_root1, ru.url, '/') AS cityurl,
                                CONCAT(:url_root2, ru.url, '/', pp.pt_slugline, '.html') AS objurl,
                                CONCAT(ru.url, '/', pp.pt_slugline, '.html') AS objuri
                            FROM $dbpp AS pp
                                LEFT JOIN $dbpr pt ON pt.tp_id = pp.pt_type_id
                                LEFT JOIN $dbpc pc ON pc.pc_id = pp.pt_citypage_id
                                    LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                            WHERE pp.pt_active = 1
                                AND pp.pt_latitude BETWEEN :bounds_min_lat AND :bounds_max_lat
                                AND pp.pt_longitude BETWEEN :bounds_min_lon AND :bounds_max_lon
                                OR pp.pt_id = :selected_object_id1
                            ORDER BY pt.tr_order DESC, pp.pt_rank
                            LIMIT 300";
        
        $this->db->execute(array(
            ':url_root1' => _URL_ROOT,
            ':url_root2' => _URL_ROOT,
            ':selected_object_id1' => $selected_object_id,
            ':selected_object_id2' => $selected_object_id,
            ':bounds_min_lat' => $bounds['min_lat'],
            ':bounds_max_lat' => $bounds['max_lat'],
            ':bounds_min_lon' => $bounds['min_lon'],
            ':bounds_max_lon' => $bounds['max_lon'],
        ));
        //$this->db->showSQL();
        while ($pt = $this->db->fetch()) {
            $pt['pt_description'] = strip_tags($pt['pt_description']);
            $pt['pt_description'] = html_entity_decode($pt['pt_description'], ENT_QUOTES, 'UTF-8');
            $short_end = @mb_strpos($pt['pt_description'], ' ', 50, 'utf-8');
            $pt['pt_short'] = trim(mb_substr($pt['pt_description'], 0, $short_end, 'utf-8'), "\x00..\x1F,.-");
            $pt['pt_website'] = htmlspecialchars($pt['pt_website'], ENT_QUOTES);
            $points[] = $pt;
        }

        $this->smarty->assign('ptypes', $ptypes);
        $this->smarty->assign('bounds', $bounds);
        $this->smarty->assign('points', $points);

        header("Content-type: application/xml");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Expires: " . date("r"));
        echo $this->smarty->fetch(_DIR_TEMPLATES . '/_XML/YMapsML3.sm.xml');
        exit();
    }
    
    private function getRefPointTypes() {
        $dbpr = $this->db->getTableName('ref_pointtypes');
        $this->db->sql = "SELECT * FROM $dbpr";
        $this->db->exec();
        $ptypes = $this->db->fetchAll();
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}

