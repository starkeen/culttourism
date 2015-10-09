<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        parent::__construct($db, 'gps', $page_id);
        $id = urldecode($id);
        if (strpos($id, '?') !== FALSE)
            $id = substr($id, 0, strpos($id, '?'));
        $this->id = $id;

        //========================  I N D E X  ================================
        if ($page_id == '') {
            $this->smarty->assign('gps_text', $this->content);
            $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/gps/gps.sm.html');
        }
        //=======================  E X P O R T  ===============================
        elseif ($page_id == 'export') {
            if (isset($_POST['pts']) && !empty($_POST['pts']) && (
                    isset($_POST['submit_gpx']) ||
                    isset($_POST['submit_kml']))) {

                $list_points = array();
                $export_points = array();
                foreach ($_POST['pts'] as $ptid => $pv) {
                    $list_points[] = cut_trash_int($ptid);
                }
                $dbpp = $db->getTableName('pagepoints');
                $dbpc = $db->getTableName('pagecity');
                $dbru = $db->getTableName('region_url');
                $dbse = $db->getTableName('statexport');

                $db->sql = "SELECT pt_id, pt_name, pt_adress, pt_phone, pt_description,
                            pt_latitude, pt_longitude, pt_citypage_id,
                            DATE_FORMAT(pt_lastup_date, '%Y-%m-%dT%H:%i:%sZ') as pt_date
                            FROM $dbpp
                            WHERE pt_id IN (" . implode(',', $list_points) . ")
                                AND pt_latitude != ''
                                AND pt_longitude != ''";
                $db->exec();
                while ($row = $db->fetch()) {
                    $row['pt_text'] = $row['pt_description'];
                    $export_points[] = $row;
                }
                $city_id = $export_points[0]['pt_citypage_id'];

                $db->sql = "SELECT pc_title, pc_inwheretext, pc_title_translit, pc_latitude, pc_longitude,
                            url
                            FROM $dbpc pc
                            LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                            WHERE pc_id = '$city_id'";
                $db->exec();
                $region = $db->fetch();

                if (isset($_POST['submit_gpx'])) {
                    $export_type = 'gpx';
                } elseif (isset($_POST['submit_kml'])) {
                    $export_type = 'kml';
                } else {
                    $export_type = '';
                }

                $hash = $this->getUserHash();
                $db->sql = "INSERT INTO $dbse (se_citypage_id, se_points, se_type, se_userhash, se_date) VALUES ";
                $sql_pnts = '';
                foreach ($list_points as $pnt_id) {
                    $sql_pnts[] = "('$city_id', '$pnt_id', '$export_type', '$hash', now())";
                }
                $db->sql .= implode(', ', $sql_pnts);
                $db->exec();

                $this->smarty->assign('points', $export_points);
                $this->smarty->assign('region', $region);

                $file_content = '';
                if ($export_type == 'gpx') {
                    $file_content = $this->smarty->fetch(_DIR_TEMPLATES . '/_XML/GPX.export.sm.xml');
                    header("Content-type: application/gpx+xml");
                    header("Content-Disposition: attachment; filename=culttourism_GPX_{$region['pc_title_translit']}.gpx");
                } elseif ($export_type == 'kml') {
                    $file_content = $this->smarty->fetch(_DIR_TEMPLATES . '/_XML/KML.export.sm.xml');
                    header("Content-type: application/vnd.google-earth.kml+xml");
                    header("Content-Disposition: attachment; filename=culttourism_KML_{$region['pc_title_translit']}.kml");
                }
                echo $file_content;
                exit();
            } else {
                $this->getError('301', '../');
            }
        }
        //==========================  E X I T  ================================
        else {
            $this->getError('404');
        }
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
