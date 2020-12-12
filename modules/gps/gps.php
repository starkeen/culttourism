<?php

use app\core\SiteRequest;
use app\db\MyDB;

class Page extends PageCommon
{
    /**
     * @inheritDoc
     */
    protected function compileContent(): void
    {
        $page_id = $this->siteRequest->getLevel1();
        $id = $this->siteRequest->getLevel2();
        $id = urldecode($id);
        if (strpos($id, '?') !== false) {
            $id = substr($id, 0, strpos($id, '?'));
        }
        $this->id = $id;

        //========================  I N D E X  ================================
        if ($page_id == '') {
            $this->smarty->assign('gps_text', $this->content);
            $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/gps/gps.sm.html');
        } //=======================  E X P O R T  ===============================
        elseif ($page_id === 'export') {
            if (isset($_POST['pts']) && !empty($_POST['pts']) && (
                    isset($_POST['submit_gpx']) ||
                    isset($_POST['submit_kml']))) {

                $list_points = [];
                $export_points = [];
                foreach ($_POST['pts'] as $ptid => $pv) {
                    $list_points[] = cut_trash_int($ptid);
                }
                $dbpp = $this->db->getTableName('pagepoints');
                $dbpc = $this->db->getTableName('pagecity');
                $dbru = $this->db->getTableName('region_url');
                $dbse = $this->db->getTableName('statexport');

                $this->db->sql = "SELECT pt_id, pt_name, pt_adress, pt_phone, pt_description,
                            pt_latitude, pt_longitude, pt_citypage_id,
                            DATE_FORMAT(pt_lastup_date, '%Y-%m-%dT%H:%i:%sZ') as pt_date
                            FROM $dbpp
                            WHERE pt_id IN (" . implode(',', $list_points) . ")
                                AND pt_latitude != ''
                                AND pt_longitude != ''";
                $this->db->exec();
                while ($row = $this->db->fetch()) {
                    $row['pt_text'] = $row['pt_description'];
                    $export_points[] = $row;
                }
                $city_id = $export_points[0]['pt_citypage_id'];

                $this->db->sql = "SELECT pc_title, pc_inwheretext, pc_title_translit, pc_latitude, pc_longitude,
                            url
                            FROM $dbpc pc
                            LEFT JOIN $dbru ru ON ru.uid = pc.pc_url_id
                            WHERE pc_id = '$city_id'";
                $this->db->exec();
                $region = $this->db->fetch();

                if (isset($_POST['submit_gpx'])) {
                    $export_type = 'gpx';
                } elseif (isset($_POST['submit_kml'])) {
                    $export_type = 'kml';
                } else {
                    $export_type = '';
                }

                $hash = $this->getUserHash();
                $this->db->sql = "INSERT INTO $dbse (se_citypage_id, se_points, se_type, se_userhash, se_date) VALUES ";
                $sql_pnts = [];
                foreach ($list_points as $pnt_id) {
                    $sql_pnts[] = "('$city_id', '$pnt_id', '$export_type', '$hash', now())";
                }
                $this->db->sql .= implode(', ', $sql_pnts);
                $this->db->exec();

                $this->smarty->assign('points', $export_points);
                $this->smarty->assign('region', $region);

                $file_content = '';
                if ($export_type === 'gpx') {
                    $file_content = $this->smarty->fetch(_DIR_TEMPLATES . '/_XML/GPX.export.sm.xml');
                    header('Content-type: application/gpx+xml');
                    header(
                        "Content-Disposition: attachment; filename=culttourism_GPX_{$region['pc_title_translit']}.gpx"
                    );
                } elseif ($export_type === 'kml') {
                    $file_content = $this->smarty->fetch(_DIR_TEMPLATES . '/_XML/KML.export.sm.xml');
                    header('Content-type: application/vnd.google-earth.kml+xml');
                    header(
                        "Content-Disposition: attachment; filename=culttourism_KML_{$region['pc_title_translit']}.kml"
                    );
                }
                echo $file_content;
                exit();
            } else {
                $this->processError(Core::HTTP_CODE_301, '../');
            }
        } //==========================  E X I T  ================================
        else {
            $this->processError(Core::HTTP_CODE_404);
        }
    }

    public static function getInstance(MyDB $db, SiteRequest $request): self
    {
        return self::getInstanceOf(__CLASS__, $db, $request);
    }
}
