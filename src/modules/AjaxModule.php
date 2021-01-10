<?php

declare(strict_types=1);

namespace app\modules;

use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\AccessDeniedException;
use app\exceptions\NotFoundException;
use app\sys\TemplateEngine;
use MDataCheck;
use MModules;
use models\MLinks;
use MPageCities;
use MPagePoints;
use MRefPointtypes;
use MStatpoints;

class AjaxModule implements ModuleInterface
{
    private MDataCheck $mDataCheck;

    private MyDB $db;

    private TemplateEngine $templateEngine;

    private WebUser $webUser;

    public function __construct(MyDB $db, TemplateEngine $templateEngine, WebUser $webUser)
    {
        $this->db = $db;
        $this->templateEngine = $templateEngine;
        $this->webUser = $webUser;
    }

    /**
     * @inheritDoc
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        $id = $request->getLevel2();
        if ($id === null) {
            throw new NotFoundException();
        }

        $id = urldecode($id);
        if (str_contains($id, '?')) {
            $id = substr($id, 0, strpos($id, '?'));
        }

        $this->webUser->getAuth()->setService('ajax');

        $this->mDataCheck = new MDataCheck($this->db);

        if ($request->getLevel1() === 'point') {
            if ($id == '' && isset($_GET['id']) && (int) $_GET['id']) {
                $response->getContent()->setBody($this->getPoint((int) $_GET['id']));
            } elseif ($id === 's' && isset($_GET['id'])) {
                $slugLine = str_replace('.html', '', $_GET['id']);
                $response->getContent()->setJsonHtml($this->getPointBySlugLine($slugLine));
            } elseif ($id === 'getnewform' && isset($_GET['cid'])) {
                $response->getContent()->setJsonHtml($this->getPointNew((int) $_GET['cid']));
            } elseif ($id === 'savenew' && isset($_GET['cid'])) {
                $response->getContent()->setBody((string) $this->savePointNew((int) $_GET['cid']));
            } elseif ($id === 'delpoint' && isset($_GET['pid'])) {
                $response->getContent()->setBody((string) $this->deletePoint((int) $_GET['pid']));
            } elseif ($id === 'getformGPS' && isset($_GET['pid'])) {
                $response->getContent()->setJsonHtml($this->getFormPointGPS((int) $_GET['pid']));
            } elseif ($id === 'saveformGPS' && isset($_GET['pid'])) {
                $response->getContent()->setBody($this->setFormPointGPS((int) $_GET['pid']));
            } elseif ($id === 'saveAddrGPS' && isset($_GET['pid'])) {
                $response->getContent()->setBody((string) $this->setFormPointAddr((int) $_GET['pid']));
            } elseif ($id === 'savebest') {
                $response->getContent()->setBody((string) $this->setFormPointBest((int) $_GET['id']));
            }
            $response->setLastEditTimestampToFuture();
        } elseif ($request->getLevel1() === 'city') {
            if ($id === 'savetitle' && isset($_GET['id']) && (int) $_GET['id']) {
                $response->getContent()->setBody($this->saveCityTitle((int) $_GET['id']));
            } elseif ($id === 'savedescr' && isset($_GET['id']) && (int) $_GET['id']) {
                $response->getContent()->setBody($this->saveCityDescription((int) $_GET['id']));
            } elseif ($id === 'getformGPS' && isset($_GET['cid']) && (int) $_GET['cid']) {
                $response->getContent()->setJsonHtml($this->getFormCityGPS((int) $_GET['cid']));
            } elseif ($id === 'saveformGPS') {
                $response->getContent()->setBody((string) $this->setFormCityGPS((int) $_GET['cid']));
            }
            $response->setLastEditTimestampToFuture();
        } elseif ($request->getLevel1() === 'pointtype') {
            if ($id === 'getform') {
                $response->getContent()->setJsonHtml($this->getChangeTypeForm());
            } elseif ($id === 'savetype' && isset($_POST['pid']) && (int) $_POST['pid']) {
                $response->getContent()->setBody($this->setPointType((int) $_POST['pid']));
            }
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === 'ajax';
    }

    /**
     * -------------------------------------------------------------- POINTS ----------
     * /

    /**
     * @param int $pid
     * @return bool
     * @throws AccessDeniedException
     */
    private function setFormPointAddr(int $pid): bool
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
     * @param int $pid
     * @return bool
     * @throws AccessDeniedException
     */
    private function setFormPointBest(int $pid): bool
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $state = (int) (!empty($_POST['nstate']) && $_POST['nstate'] === 'checked');
        $p = new MPagePoints($this->db);

        return $p->updateByPk($pid, ['pt_is_best' => $state]);
    }

    /**
     * @param int $pid
     * @return null|string
     * @throws AccessDeniedException
     */
    private function setFormPointGPS(int $pid): ?string
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
        }

        return null;
    }

    /**
     * @param int $pid
     * @return string
     */
    private function getFormPointGPS(int $pid): string
    {
        $pt = new MPagePoints($this->db);
        $point = $pt->getItemByPk($pid);

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
        $point['zoom'] = (int) $point['pt_latlon_zoom'] !== 0 ? (int) $point['pt_latlon_zoom'] : 13;
        $this->templateEngine->assign('point', $point);

        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_ajax/changelatlon.form.tpl');
    }

    /**
     * @return string
     * @throws AccessDeniedException
     */
    private function getChangeTypeForm(): string
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $point_id = (int) $_GET['pid'];
        if ($point_id === 0) {
            throw new AccessDeniedException();
        }
        $p = new MPagePoints($this->db);
        $pts = new MRefPointtypes($this->db);

        $point = $p->getItemByPk((int) $_GET['pid']);
        $types = $pts->getActive();
        foreach ($types as $i => $type) {
            $types[$i]['current'] = ((int) $type['tp_id'] === (int) $point['pt_type_id']) ? 1 : 0;
        }

        $this->templateEngine->assign('point', $point);
        $this->templateEngine->assign('alltypes', $types);

        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_ajax/changetype.form.tpl');
    }

    /**
     * @param int $pid
     * @return string
     * @throws AccessDeniedException
     */
    private function setPointType(int $pid): string
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $ppid = (int) $_POST['pid'];
        $type = (int) $_POST['ntype'];
        if ($pid !== $ppid || $type === 0) {
            throw new AccessDeniedException();
        }

        $p = new MPagePoints($this->db);
        $pts = new MRefPointtypes($this->db);

        $p->updateByPk(
            $pid,
            [
                'pt_type_id' => $type,
            ]
        );
        $newType = $pts->getItemByPk($type);

        return (string) $newType['tp_icon'];
    }

    /**
     * @param int $id
     * @return string
     * @throws AccessDeniedException
     */
    private function getPointNew(int $id): string
    {
        if ($this->webUser->isEditor()) {
            if ($id !== 0) {
                $pc = new MPageCities($this->db);
                $city = $pc->getItemByPk($id);
                $city_title = 'г. ' . $city['pc_title'];
            } else {
                $city_title = '';
            }
            $this->templateEngine->assign('city_title', $city_title);

            return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_pages/ajaxpoint.add.tpl');
        }

        throw new AccessDeniedException();
    }

    /**
     * @param int $pid
     * @return null|int
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function deletePoint(int $pid): ?int
    {
        if ($pid === 0) {
            throw new NotFoundException();
        }
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        $ppid = (int) $_POST['pid'];
        if ($pid !== $ppid) {
            throw new AccessDeniedException();
        }
        $pp = new MPagePoints($this->db);
        $state = $pp->deleteByPk($ppid);
        if ($state) {
            $linksModel = new MLinks($this->db);
            $linksModel->deleteByPoint($ppid);

            return $ppid;
        }

        return null;
    }

    /**
     * @param int $cid
     * @return int
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function savePointNew(int $cid): int
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

        return (int) $pts->insert($add_item);
    }

    /**
     * @param int $id
     * @return string
     * @throws NotFoundException
     */
    private function getPoint(int $id): string
    {
        if ($id === 0) {
            throw new NotFoundException();
        }

        $pts = new MPagePoints($this->db);
        $object = $pts->getItemByPk($id);

        if (!$object) {
            throw new NotFoundException();
        }

        $object['page_link'] = $object['url_canonical'];
        $object['gps_dec'] = '';

        $sp = new MStatpoints($this->db);
        $sp->add($object['pt_id'], $this->webUser->getHash());

        $this->templateEngine->assign('object', $object);

        if ($this->webUser->isEditor()) {
            return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_pages/ajaxpoint.edit.tpl');
        }

        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_pages/ajaxpoint.show.tpl');
    }

    /**
     * @param string $slugLine
     * @return null|string
     * @throws NotFoundException
     */
    private function getPointBySlugLine(string $slugLine): ?string
    {
        if ($slugLine === '') {
            throw new NotFoundException();
        }

        $pts = new MPagePoints($this->db);
        $objects = $pts->searchSlugline($slugLine);
        $object = $objects[0] ?? false;
        if (!$object) {
            return null;
        }

        $object['page_link'] = $object['url_canonical'];

        $sp = new MStatpoints($this->db);
        $sp->add($object['pt_id'], $this->webUser->getHash());

        $this->templateEngine->assign('object', $object);

        if ($this->webUser->isEditor()) {
            return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_pages/ajaxpoint.edit.tpl');
        }

        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_pages/ajaxpoint.show.tpl');
    }

    /**
     * -------------------------------------------------------------- CITY ----------
     * @param int $cid
     * @return bool|null
     * @throws AccessDeniedException
     */
    private function setFormCityGPS(int $cid): ?bool
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
        }

        return false;
    }

    /**
     * @param int $cid
     * @return string
     */
    private function getFormCityGPS(int $cid): string
    {
        $pc = new MPageCities($this->db);
        $city = $pc->getItemByPk($cid);

        if ($city['pc_latitude'] && $city['pc_longitude']) {
            $city['map_center']['lat'] = $city['pc_latitude'];
            $city['map_center']['lon'] = $city['pc_longitude'];
            $city['zoom'] = $city['pc_latlon_zoom'] ?: 13;
            $city['map_point'] = 1;
        } else {
            $city['map_center']['lat'] = 55.7557;
            $city['map_center']['lon'] = 37.6176;
            $city['zoom'] = 3;
            $city['map_point'] = -1;
        }

        $this->templateEngine->assign('city', $city);

        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_ajax/citylatlon.form.tpl');
    }

    /**
     * @param int $id
     * @return string
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function saveCityTitle(int $id): string
    {
        if (!$id) {
            throw new NotFoundException();
        }
        $nid = (int) $_POST['id'];
        if ($id !== $nid) {
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
        }

        throw new NotFoundException();
    }

    /**
     * @param int $id
     * @return string
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    private function saveCityDescription(int $id): string
    {
        if ($id === 0) {
            throw new NotFoundException();
        }
        $nid = (int) $_POST['id'];
        if ($id !== $nid) {
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
        }

        throw new NotFoundException();
    }
}
