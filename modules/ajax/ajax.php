<?php

use app\core\SiteRequest;
use app\db\MyDB;
use app\exceptions\AccessDeniedException;
use app\exceptions\NotFoundException;
use models\MLinks;

class Page extends Core
{
    /**
     * @var MDataCheck
     */
    private $mDataCheck;

    /**
     * @inheritDoc
     * @throws NotFoundException
     */
    public function compileContent(): void
    {
        $id = $this->siteRequest->getLevel2();
        $this->smarty->caching = false;
        $id = urldecode($id);
        if (strpos($id, '?') !== false) {
            $id = substr($id, 0, strpos($id, '?'));
        }
        $this->id = $id;
        $this->auth->setService('ajax');

        $this->mDataCheck = new MDataCheck($this->db);

        if ($this->siteRequest->getLevel1() === 'point') {
            if ($id == '' && isset($_GET['id']) && (int) $_GET['id']) {
                $this->pageContent->setBody($this->getPoint((int) $_GET['id']));
            } elseif ($id === 's' && isset($_GET['id'])) {
                $this->pageContent->setBody($this->getPointBySlugLine(str_replace('.html', '', $_GET['id'])));
            } elseif ($id === 'savetitle' && isset($_GET['id']) && (int) $_GET['id']) {
                $this->pageContent->setBody($this->savePointTitle((int) $_GET['id']));
            } elseif ($id === 'savedescr' && isset($_GET['id']) && (int) $_GET['id']) {
                $this->pageContent->setBody($this->savePointDescr((int) $_GET['id']));
            } elseif ($id === 'savecontacts' && isset($_GET['cid']) && (int) $_GET['cid']) {
                $this->pageContent->setBody($this->savePointContacts((int) $_GET['cid']));
            } elseif ($id === 'getnewform' && isset($_GET['cid'])) {
                $this->pageContent->setBody($this->getPointNew((int) $_GET['cid']));
            } elseif ($id === 'savenew' && isset($_GET['cid'])) {
                $this->pageContent->setBody($this->savePointNew((int) $_GET['cid']));
            } elseif ($id === 'delpoint' && isset($_GET['pid'])) {
                $this->pageContent->setBody($this->deletePoint((int) $_GET['pid']));
            } elseif ($id === 'getformGPS' && isset($_GET['pid'])) {
                $this->pageContent->setBody($this->getFormPointGPS((int) $_GET['pid']));
            } elseif ($id === 'saveformGPS' && isset($_GET['pid'])) {
                $this->pageContent->setBody($this->setFormPointGPS((int) $_GET['pid']));
            } elseif ($id === 'saveAddrGPS' && isset($_GET['pid'])) {
                $this->pageContent->setBody($this->setFormPointAddr((int) $_GET['pid']));
            } elseif ($id === 'savebest') {
                $this->pageContent->setBody($this->setFormPointBest((int) $_GET['id']));
            }
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 1, 2050);
        } elseif ($this->siteRequest->getLevel1() === 'city') {
            if ($id === 'savetitle' && isset($_GET['id']) && (int) $_GET['id']) {
                $this->pageContent->setBody($this->saveCityTitle((int) $_GET['id']));
            } elseif ($id === 'savedescr' && isset($_GET['id']) && (int) $_GET['id']) {
                $this->pageContent->setBody($this->saveCityDescription((int) $_GET['id']));
            } elseif ($id === 'getformGPS' && isset($_GET['cid']) && (int) $_GET['cid']) {
                $this->pageContent->setBody($this->getFormCityGPS((int) $_GET['cid']));
            } elseif ($id === 'saveformGPS') {
                $this->pageContent->setBody($this->setFormCityGPS((int) $_GET['cid']));
            }
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 1, 2050);
        } elseif ($this->siteRequest->getLevel1() === 'pointtype') {
            if ($id === 'getform') {
                $this->pageContent->setBody($this->getChangeTypeForm());
            } elseif ($id === 'savetype' && isset($_POST['pid']) && (int) $_POST['pid']) {
                $this->pageContent->setBody($this->setPointType((int) $_POST['pid']));
            }
        } elseif ($this->siteRequest->getLevel1() === 'page') {
            if ($id === 'gps') {
                $this->pageContent->setBody($this->getTextPage(31));
            } else {
                throw new NotFoundException();
            }
        } else {
            throw new NotFoundException();
        }
    }

//--------------------------------------------------------- TEXT PAGES ---------
    private function getTextPage($pg_id): string
    {
        $mds = new MModules($this->db);
        $md = $mds->getItemByPk($pg_id);

        return '<h3>Экспорт данных GPS</h3>' . $md['md_pagecontent'];
    }

    /**
     * -------------------------------------------------------------- POINTS ----------
     * @param int $cid
     * @return bool|null
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function savePointContacts(int $cid): ?bool
    {
        if ($cid === 0) {
            throw new NotFoundException();
        }
        $pp = new MPagePoints($this->db);

        $nid = (int) $_POST['cid'];
        if ($cid !== $nid) {
            throw new NotFoundException();
        }
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $out = $pp->updateByPk(
            $cid,
            [
                'pt_lastup_user' => $this->webUser->getId(),
                'pt_lastup_date' => $pp->now(),
                'pt_website' => $_POST['nwebsite'],
                'pt_email' => $_POST['nemail'],
                'pt_phone' => $_POST['nphone'],
                'pt_worktime' => $_POST['nworktime'],
                'pt_adress' => $_POST['nadress'],
            ]
        );
        if ($out) {
            $this->mDataCheck->deleteChecked(MDataCheck::ENTITY_POINTS, $cid);
            $linksModel = new MLinks($this->db);
            $linksModel->deleteByPoint($cid);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $pid
     * @return bool
     * @throws AccessDeniedException
     */
    private function setFormPointAddr($pid): bool
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $p = new MPagePoints($this->db);
        $this->mDataCheck->deleteChecked(MDataCheck::ENTITY_POINTS, $pid);

        $linksModel = new MLinks($this->db);
        $linksModel->deleteByPoint($pid);

        return $p->updateByPk($pid, ['pt_adress' => $_POST['addr']]);
    }

    /**
     * @param $pid
     * @return bool
     * @throws AccessDeniedException
     */
    private function setFormPointBest($pid): bool
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $state = (int) (!empty($_POST['nstate']) && $_POST['nstate'] === 'checked');
        $p = new MPagePoints($this->db);

        return $p->updateByPk($pid, ['pt_is_best' => $state]);
    }

    /**
     * @param $pid
     * @return false|string
     * @throws AccessDeniedException
     */
    private function setFormPointGPS($pid)
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }

        $p = new MPagePoints($this->db);
        $state = $p->updateByPk(
            $pid,
            [
                'pt_latitude' => $_POST['pt_lat'],
                'pt_longitude' => $_POST['pt_lon'],
                'pt_latlon_zoom' => (int) $_POST['pt_zoom'],
            ]
        );

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

    private function getFormPointGPS($pid)
    {
        $pt = new MPagePoints($this->db);
        $point = $pt->getItemByPk($pid);
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
        $this->smarty->assign('point', $point);

        return $this->smarty->fetch(_DIR_TEMPLATES . '/_ajax/changelatlon.form.sm.html');
    }

    /**
     * @return false|string
     * @throws AccessDeniedException
     */
    private function getChangeTypeForm()
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $point_id = (int) $_GET['pid'];
        if (!$point_id) {
            throw new AccessDeniedException();
        }
        $p = new MPagePoints($this->db);
        $pts = new MRefPointtypes($this->db);

        $point = $p->getItemByPk((int) $_GET['pid']);
        $types = $pts->getActive();
        foreach ($types as $i => $type) {
            $types[$i]['current'] = ($type['tp_id'] == $point['pt_type_id']) ? 1 : 0;
        }

        $this->smarty->assign('point', $point);
        $this->smarty->assign('alltypes', $types);

        return $this->smarty->fetch(_DIR_TEMPLATES . '/_ajax/changetype.form.sm.html');
    }

    /**
     * @param $pid
     * @return mixed
     * @throws AccessDeniedException
     */
    private function setPointType($pid)
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $ppid = (int) $_POST['pid'];
        $type = (int) $_POST['ntype'];
        if ($pid != $ppid || !$type) {
            throw new AccessDeniedException();
        }

        $p = new MPagePoints($this->db);
        $pts = new MRefPointtypes($this->db);

        $state = $p->updateByPk(
            $pid,
            [
                'pt_type_id' => $type,
            ]
        );
        $newtype = $pts->getItemByPk($type);

        return $newtype['tp_icon'];
    }

    /**
     * @param $id
     * @return false|string
     * @throws AccessDeniedException
     */
    private function getPointNew($id)
    {
        if ($this->webUser->isEditor()) {
            if ($id) {
                $pc = new MPageCities($this->db);
                $city = $pc->getItemByPk($id);
                $city_title = 'г. ' . $city['pc_title'];
            } else {
                $city_title = '';
            }
            $this->smarty->assign('city_title', $city_title);

            return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.add.sm.html');
        } else {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param $pid
     * @return false|int
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function deletePoint($pid)
    {
        if (!$pid) {
            throw new NotFoundException();
        }
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $ppid = (int) $_POST['pid'];
        if ($pid != $ppid) {
            throw new AccessDeniedException();
        }
        $pp = new MPagePoints($this->db);
        $state = $pp->deleteByPk($ppid);
        if ($state) {
            $linksModel = new MLinks($this->db);
            $linksModel->deleteByPoint($ppid);

            return $ppid;
        } else {
            return false;
        }
    }

    /**
     * @param $cid
     * @return int
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function savePointNew($cid): int
    {
        if (!$cid) {
            throw new NotFoundException();
        }
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $pts = new MPagePoints($this->db);
        $add_item = [
            'pt_name' => trim($_POST['nname']) !== '' ? trim($_POST['nname']) : '[не указано]',
            'pt_description' => trim($_POST['ndesc']),
            'pt_citypage_id' => (int) $_POST['cid'],
            'pt_website' => trim($_POST['nweb']),
            'pt_email' => trim($_POST['nmail']),
            'pt_worktime' => trim($_POST['nwork']),
            'pt_adress' => trim($_POST['naddr']),
            'pt_phone' => trim($_POST['nphone']),
            'pt_is_best' => (int) (!empty($_POST['nbest']) && $_POST['nbest'] === 'checked'),
            'pt_rank' => 0,
        ];
        if ($_POST['nlat'] != '' && $_POST['nlon'] != '') {
            $add_item['pt_latitude'] = trim($_POST['nlat']);
            $add_item['pt_longitude'] = trim($_POST['nlon']);
        }

        return $pts->insert($add_item);
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function savePointTitle($id)
    {
        if (!$id) {
            throw new NotFoundException();
        }
        $nid = (int) $_POST['id'];
        if ($id != $nid) {
            throw new NotFoundException();
        }
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $pp = new MPagePoints($this->db);
        $state = $pp->updateByPk(
            $nid,
            [
                'pt_name' => $_POST['nname'],
                'pt_lastup_user' => $this->webUser->getId(),
            ]
        );
        if ($state) {
            $this->mDataCheck->deleteChecked(MDataCheck::ENTITY_POINTS, $nid);
            $point = $pp->getItemByPk($nid);

            return $point['pt_name'];
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function savePointDescr($id)
    {
        if (!$id) {
            throw new NotFoundException();
        }
        $nid = (int) $_POST['id'];
        if ($id != $nid) {
            throw new NotFoundException();
        }
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $pp = new MPagePoints($this->db);
        $state = $pp->updateByPk(
            $nid,
            [
                'pt_description' => $_POST['ndesc'],
                'pt_lastup_user' => $this->webUser->getId(),
            ]
        );
        if ($state) {
            $this->mDataCheck->deleteChecked(MDataCheck::ENTITY_POINTS, $nid);
            $point = $pp->getItemByPk($nid);

            return $point['pt_description'];
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @param $id
     * @return false|string
     * @throws NotFoundException
     */
    private function getPoint($id)
    {
        if (!$id) {
            throw new NotFoundException();
        }

        $pts = new MPagePoints($this->db);
        $object = $pts->getItemByPk($id);

        if (!$object) {
            return false;
        }

        $object['page_link'] = $object['url_canonical'];
        $object['gps_dec'] = '';

        $sp = new MStatpoints($this->db);
        $sp->add($object['pt_id'], $this->webUser->getHash());

        $this->smarty->assign('object', $object);

        if ($this->webUser->isEditor()) {
            return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.edit.sm.html');
        } else {
            return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.show.sm.html');
        }
    }

    /**
     * @param $slugLine
     * @return false|string
     * @throws NotFoundException
     */
    private function getPointBySlugLine($slugLine)
    {
        if (!$slugLine) {
            throw new NotFoundException();
        }

        $pts = new MPagePoints($this->db);
        $objects = $pts->searchSlugline($slugLine);
        $object = $objects[0] ?? false;
        if (!$object) {
            return false;
        }

        $object['page_link'] = $object['url_canonical'];

        $sp = new MStatpoints($this->db);
        $sp->add($object['pt_id'], $this->webUser->getHash());

        $this->smarty->assign('object', $object);

        if ($this->webUser->isEditor()) {
            return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.edit.sm.html');
        } else {
            return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/ajaxpoint.show.sm.html');
        }
    }

    /**
     * -------------------------------------------------------------- CITY ----------
     * @throws AccessDeniedException
     */
    private function setFormCityGPS($cid): ?bool
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $pc = new MPageCities($this->db);
        $state = $pc->updateByPk(
            $cid,
            [
                'pc_latitude' => $_POST['pc_lat'],
                'pc_longitude' => $_POST['pc_lon'],
                'pc_latlon_zoom' => $_POST['pc_zoom'],
            ]
        );

        if ($state) {
            return true;
        } else {
            return false;
        }
    }

    private function getFormCityGPS($cid)
    {
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

        $this->smarty->assign('city', $city);

        return $this->smarty->fetch(_DIR_TEMPLATES . '/_ajax/citylatlon.form.sm.html');
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function saveCityTitle($id)
    {
        if (!$id) {
            throw new NotFoundException();
        }
        $nid = (int) $_POST['id'];
        if ($id != $nid) {
            throw new NotFoundException();
        }
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $pc = new MPageCities($this->db);
        $state = $pc->updateByPk(
            $nid,
            [
                'pc_title' => $_POST['ntitle'],
            ]
        );
        if ($state) {
            $this->mDataCheck->deleteChecked(MDataCheck::ENTITY_CITIES, $nid);
            $city = $pc->getItemByPk($nid);

            return $city['pc_title'];
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function saveCityDescription($id)
    {
        if (!$id) {
            throw new NotFoundException();
        }
        $nid = (int) $_POST['id'];
        if ($id != $nid) {
            throw new NotFoundException();
        }
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }

        $pc = new MPageCities($this->db);
        $state = $pc->updateByPk(
            $nid,
            [
                'pc_text' => $_POST['ntext'],
            ]
        );
        if ($state) {
            $this->mDataCheck->deleteChecked(MDataCheck::ENTITY_CITIES, $nid);
            $city = $pc->getItemByPk($nid);

            return $city['pc_text'];
        } else {
            throw new NotFoundException();
        }
    }

    public static function getInstance(MyDB $db, SiteRequest $request): self
    {
        return self::getInstanceOf(__CLASS__, $db, $request);
    }
}
