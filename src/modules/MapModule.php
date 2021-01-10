<?php

declare(strict_types=1);

namespace app\modules;

use app\cache\Cache;
use app\constant\OgType;
use app\core\GlobalConfig;
use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\sys\TemplateEngine;
use app\utils\Urls;
use MListsItems;
use MPageCities;
use MPagePoints;
use MPhotos;
use MRefPointtypes;

class MapModule implements ModuleInterface
{
    private const REPLACE_SYMBOLS = "\x00..\x1F,.-";

    private MyDB $db;

    private TemplateEngine $templateEngine;

    private WebUser $webUser;

    private GlobalConfig $globalConfig;

    public function __construct(MyDB $db, TemplateEngine $templateEngine, WebUser $webUser, GlobalConfig $globalConfig)
    {
        $this->db = $db;
        $this->templateEngine = $templateEngine;
        $this->webUser = $webUser;
        $this->globalConfig = $globalConfig;
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        $response->getContent()->setCustomJsModule($request->getModuleKey());

        //========================  I N D E X  ================================
        if ($request->getLevel1() === null) {
            $this->prepareMetaTags($response);
            $response->getContent()->getHead()->addOGMeta(OgType::TYPE(), 'website');
            $response->getContent()->setBody($this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/map/map.tpl'));
        } //====================  M A P   E N T R Y  ============================
        elseif ($request->getLevel1() === 'common') {
            $this->webUser->getAuth()->setService('map');
            $this->getYMapsMLCommon($_GET);
        } elseif ($request->getLevel1() === 'city' && isset($_GET['cid']) && (int) $_GET['cid'] > 0) {
            $this->webUser->getAuth()->setService('map');
            $this->getYMapsMLRegion((int) $_GET['cid']);
        } elseif ($request->getLevel1() === 'list' && isset($_GET['lid']) && (int) $_GET['lid'] > 0) {
            $this->webUser->getAuth()->setService('map');
            $this->getYMapsMLList((int) $_GET['lid']);
        } elseif ($request->getLevel1() === 'gpx' && isset($_GET['cid']) && (int) $_GET['cid']) {
            $this->showCityPointsGPX((int) $_GET['cid']);
        } //==========================  E X I T  ================================
        else {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'map';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }

    private function prepareMetaTags(SiteResponse $response): void
    {
        $photo = $this->getPhotosModel()->getItemByPk(2270);
        $objImage = Urls::getAbsoluteURL($photo['ph_src']);
        $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $objImage);
        $response->getContent()->getHead()->addMicroData('image', $objImage);

        $response->getContent()->getHead()->addTitleElement($this->globalConfig->getDefaultPageTitle());
        $response->getContent()->getHead()->addTitleElement('Достопримечательности на интерактивной карте');

        $response->getContent()->setH1('Достопримечательности на карте');
        $response->getContent()->getHead()->addKeyword($this->globalConfig->getDefaultPageKeywords());
        $response->getContent()->getHead()->addKeyword('карта, схема, достопримечательности, достопримечательности, расстояния');
        $response->getContent()->getHead()->addDescription($this->globalConfig->getDefaultPageDescription());
        $response->getContent()->getHead()->addDescription('Перед поездкой вы можете наглядно спланировать свой маршрут. Найдите на карте все достопримечательности в окрестностях интересного вам места, города или даже региона');

        $response->getContent()->getHead()->setCanonicalUrl('/map/');
    }

    /**
     * @param int $listId
     */
    private function getYMapsMLList(int $listId): void
    {
        $bounds = [
            'max_lat' => 0,
            'max_lon' => 0,
            'min_lat' => 180,
            'min_lon' => 180,
            'center_lat' => null,
            'center_lon' => null,
            'delta_lat' => 0.1,
            'delta_lon' => 0.3,
        ];

        $pointTypes = $this->getRefPointTypes();

        $li = new MListsItems($this->db);
        $points = $li->getPointsInList($listId);
        foreach ($points as $i => $pt) {
            $points[$i]['pt_description'] = strip_tags($points[$i]['pt_description']);
            $points[$i]['pt_description'] = html_entity_decode($points[$i]['pt_description'], ENT_QUOTES, 'UTF-8');
            $points[$i]['pt_short'] = $this->getShortDescription($points[$i]['pt_description'], 50);
            $points[$i]['pt_website'] = htmlspecialchars($points[$i]['pt_website'] ?? '', ENT_QUOTES);

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
            $bounds['min_lat'] -= $bounds['delta_lat'];
            $bounds['max_lat'] += $bounds['delta_lat'];
            $bounds['min_lon'] -= $bounds['delta_lon'];
            $bounds['max_lon'] += $bounds['delta_lon'];
        }

        $this->templateEngine->assign('ptypes', $pointTypes);
        $this->templateEngine->assign('bounds', $bounds);
        $this->templateEngine->assign('points', $points);

        $this->sendYMLHeaders();
        echo $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_XML/YMapsML3.sm.xml');
        exit();
    }

    /**
     * @param $cid
     * @throws NotFoundException
     */
    private function getYMapsMLRegion($cid): void
    {
        if (!$cid) {
            throw new NotFoundException();
        }

        $pt = new MPagePoints($this->db);
        $pc = new MPageCities($this->db);

        $pointTypes = $this->getRefPointTypes();

        $this_city = $pc->getItemByPk($cid);
        $points = $pt->getGeoPointsByCityId($cid);
        $city = $pc->getCitiesSomeRegion($cid);

        foreach ($points as $i => $pt) {
            $points[$i]['pt_description'] = strip_tags($points[$i]['pt_description']);
            $points[$i]['pt_description'] = html_entity_decode($points[$i]['pt_description'], ENT_QUOTES, 'UTF-8');
            $points[$i]['pt_short'] = $this->getShortDescription($pt['pt_description'], 100);
            $points[$i]['pt_website'] = htmlspecialchars($points[$i]['pt_website'] ?? '', ENT_QUOTES);
        }

        if ((int) $this_city['pc_region_id'] === 0) {
            $city = array_merge($city, $pc->getCitiesSomeCountry($this_city['pc_country_id']));
        }

        $this->templateEngine->assign('ptypes', $pointTypes);
        $this->templateEngine->assign('points', $points);
        $this->templateEngine->assign('city', $city);

        $this->sendYMLHeaders();
        echo $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_XML/YMapsML1.sm.xml');
        exit();
    }

    private function getYMapsMLCommon($get): void
    {
        $pointTypes = $this->getRefPointTypes();

        $bounds = [
            'max_lat' => 55.9864578247,
            'max_lon' => 37.9002265930,
            'min_lat' => 55.4144554138,
            'min_lon' => 37.1716384888,
            'center_lat' => null,
            'center_lon' => null,
            'delta_lat' => 0.1,
            'delta_lon' => 0.3,
        ];

        if (!isset($get['center']) && isset($get['clt']) && isset($get['cln']) && !isset($get['llt']) && !isset($get['lln']) && !isset($get['rlt']) && !isset($get['rln'])) {
            //---------- по координатам центра (раздельно)
            $bounds['center_lat'] = cut_trash_float($get['clt']);
            $bounds['center_lon'] = cut_trash_float($get['cln']);
            $bounds = $this->calculateMaxMinBounds($bounds);
        } elseif (isset($get['center']) && !isset($get['clt']) && !isset($get['cln']) && !isset($get['llt']) && !isset($get['lln']) && !isset($get['rlt']) && !isset($get['rln'])) {
            //---------- по координатам центра (в одном)
            $center = explode(',', $get['center']);
            $bounds['center_lat'] = cut_trash_float($center[1]);
            $bounds['center_lon'] = cut_trash_float($center[0]);
            $bounds = $this->calculateMaxMinBounds($bounds);
        } elseif (!isset($get['center']) && isset($get['llt']) && isset($get['lln']) && isset($get['rlt']) && isset($get['rln']) && !isset($get['clt']) && !isset($get['cln'])) {
            //---------- по координатам левого и правого угла
            $bounds['max_lat'] = cut_trash_float($get['rlt']);
            $bounds['max_lon'] = cut_trash_float($get['rln']);
            $bounds['min_lat'] = cut_trash_float($get['llt']);
            $bounds['min_lon'] = cut_trash_float($get['lln']);
            $bounds = $this->calculateCenterBounds($bounds);
        } else {
            //---------- по умолчанию берем Москву
            $bounds = $this->calculateCenterBounds($bounds);
        }
        if (isset($get['oid']) && (int) $get['oid'] > 0) {
            $selected_object_id = (int) $get['oid'];
        } else {
            $selected_object_id = 0;
        }

        $pt = new MPagePoints($this->db);
        $points = $pt->getPointsByBounds($bounds, $selected_object_id);

        foreach ($points as $i => $ptItem) {
            $points[$i]['pt_description'] = strip_tags($points[$i]['pt_description']);
            $points[$i]['pt_description'] = html_entity_decode($points[$i]['pt_description'], ENT_QUOTES, 'UTF-8');
            $points[$i]['pt_short'] = $this->getShortDescription($points[$i]['pt_description'], 50);
            $points[$i]['pt_website'] = htmlspecialchars($points[$i]['pt_website'] ?? '', ENT_QUOTES);
        }

        $this->templateEngine->assign('ptypes', $pointTypes);
        $this->templateEngine->assign('bounds', $bounds);
        $this->templateEngine->assign('points', $points);

        $this->sendYMLHeaders();
        echo $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_XML/YMapsML3.sm.xml');
        exit();
    }

    /**
     * @param $cid
     * @throws NotFoundException
     */
    private function showCityPointsGPX($cid): void
    {
        if (!$cid) {
            throw new NotFoundException();
        }
        $pt = new MPagePoints($this->db);
        $pts = $pt->getPointsByCity($cid);

        $this->templateEngine->assign('points', $pts['points']);

        header('Content-type: application/xml');
        echo $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/_XML/GPX.export.sm.xml');
        exit();
    }

    /**
     * @return array
     */
    private function getRefPointTypes(): array
    {
        $cache = Cache::i('refs');
        $types = $cache->get('point_types');
        if (empty($types)) {
            $ref = new MRefPointtypes($this->db);
            $types = $ref->getActive();
            $cache->put('point_types', $types);
        }
        return $types;
    }

    /**
     * @param string $longDescription
     * @param int $size
     * @return string
     */
    private function getShortDescription(string $longDescription, int $size): string
    {
        $plainDescription = strip_tags($longDescription);
        $descriptionLength = mb_strlen($plainDescription);
        $trimBoundary = @mb_strpos($plainDescription, ' ', min($size, $descriptionLength), 'utf-8');

        return trim(
            mb_substr($plainDescription, 0, $trimBoundary ?: null, 'utf-8'),
            self::REPLACE_SYMBOLS
        );
    }

    private function calculateCenterBounds(array $bounds): array
    {
        $bounds['delta_lat'] = $bounds['max_lat'] - $bounds['min_lat'];
        $bounds['delta_lon'] = $bounds['max_lon'] - $bounds['min_lon'];
        $bounds['center_lat'] = $bounds['min_lat'] + $bounds['delta_lat'];
        $bounds['center_lon'] = $bounds['min_lon'] + $bounds['delta_lon'];

        return $bounds;
    }

    private function calculateMaxMinBounds(array $bounds): array
    {
        $bounds['max_lat'] = $bounds['center_lat'] + $bounds['delta_lat'];
        $bounds['max_lon'] = $bounds['center_lon'] + $bounds['delta_lon'];
        $bounds['min_lat'] = $bounds['center_lat'] - $bounds['delta_lat'];
        $bounds['min_lon'] = $bounds['center_lon'] - $bounds['delta_lon'];

        return $bounds;
    }

    private function sendYMLHeaders(): void
    {
        header('Content-type: application/xml');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Expires: ' . date('r'));
    }

    private function getPhotosModel(): MPhotos
    {
        return new MPhotos($this->db);
    }
}
