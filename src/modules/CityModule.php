<?php

declare(strict_types=1);

namespace app\modules;

use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\exceptions\NotFoundException;
use app\constant\OgType;
use app\exceptions\AccessDeniedException;
use app\exceptions\RedirectException;
use app\model\repository\WordstatRepository;
use app\services\openweathermap\WeatherFactory;
use app\services\openweathermap\WeatherService;
use app\utils\Strings;
use MPageCities;
use MPhotos;

class CityModule extends Module implements ModuleInterface
{
    /**
     * @var MPageCities
     */
    private $modelPageCities;

    /**
     * @var MPhotos
     */
    private $modelPhotos;

    /**
     * @var WordstatRepository
     */
    private $wordstatRepository;

    /**
     * @var WeatherService
     */
    private $weatherService;

    /**
     * @inheritDoc
     * @throws NotFoundException
     * @throws AccessDeniedException
     * @throws RedirectException
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        if ($request->getLevel1() === null) {
            $this->pageCity($response);
        } elseif ($request->getLevel1() === 'add') {
            $this->addCity($response);
        } elseif ($request->getLevel1() === 'detail') {
            $this->detailCity($response);
        } elseif ($request->getLevel1() === 'meta') {
            $this->metaCity();
        } elseif ($request->getLevel1() === 'weather' && isset($_GET['lat']) && isset($_GET['lon'])) {
            $response->setLastEditTimestampToFuture();
            $this->getBlockWeather((float) $_GET['lat'], (float) $_GET['lon']);
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'city';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }

    /**
     **************************************  БЛОК  ПОГОДЫ  *****************
     * @param float $lat
     * @param float $lon
     */
    private function getBlockWeather(float $lat, float $lon): void
    {
        $out = ['state' => false, 'content' => '', 'color' => ''];

       $weatherData = $this->getWeatherService()->getWeatherByCoordinates($lat, $lon);

        if ($weatherData !== null) {
            $out['state'] = true;
            $out['content'] = $this->templateEngine->getContent('city/weather.block.tpl', [
                'weatherData' => $weatherData,
            ]);
        }
        header('Content-type: application/json');
        echo json_encode($out);
        exit();
    }

    /**
     **************************************  ТАБЛИЦА МЕТА  *****************
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    private function metaCity(): void
    {
        $dbcd = $this->db->getTableName('city_data');
        $dbcf = $this->db->getTableName('city_fields');

        if (isset($_POST['act'])) {
            if (!$this->webUser->isEditor()) {
                throw new AccessDeniedException();
            }
            $uid = $this->webUser->getId();
            switch ($_POST['act']) {
                case 'add':
                    $cf_id = (int) $_POST['cf'];
                    $value = trim($_POST['val']);
                    $city_id = (int) $_POST['cpid'];
                    $this->db->sql = "DELETE FROM $dbcd WHERE cd_pc_id = :city_id AND cd_cf_id = :cf_id";
                    $this->db->execute(
                        [
                            ':city_id' => $city_id,
                            ':cf_id' => $cf_id,
                        ]
                    );

                    if ($value != '') {
                        $this->db->sql = "INSERT INTO $dbcd SET cd_pc_id = :city_id, cd_cf_id = :cf_id, cd_value = :cd_value";
                        $this->db->execute(
                            [
                                ':city_id' => $city_id,
                                ':cf_id' => $cf_id,
                                ':cd_value' => $value,
                            ]
                        );
                    }
                    $this->db->sql = "SELECT * FROM  $dbcf WHERE cf_id = :cf_id";
                    $this->db->execute(
                        [
                            ':cf_id' => $cf_id,
                        ]
                    );
                    $row = $this->db->fetch();
                    $this->buildModelPageCities()->updateByPk(
                        $city_id,
                        [
                            'pc_lastup_user' => $uid,
                        ]
                    );
                    echo $row['cf_title'];
                    break;
                case 'del':
                    $cf_id = (int) $_POST['cf'];
                    $city_id = (int) $_POST['cpid'];
                    $this->db->sql = "DELETE FROM $dbcd WHERE cd_pc_id = :city_id AND cd_cf_id = :cf_id";
                    $this->db->execute(
                        [
                            ':city_id' => $city_id,
                            ':cf_id' => $cf_id,
                        ]
                    );
                    $this->buildModelPageCities()->updateByPk(
                        $city_id,
                        [
                            'pc_lastup_user' => $uid,
                        ]
                    );
                    echo 'ok';
                    break;
                case 'edit':
                    $cf_id = (int) $_POST['cf'];
                    $city_id = (int) $_POST['cpid'];
                    $value = trim($_POST['val']);
                    if ($value != '') {
                        $this->db->sql = "UPDATE $dbcd SET cd_value = :cd_value WHERE cd_pc_id = :city_id AND cd_cf_id = :cf_id";
                        $this->db->execute(
                            [
                                ':city_id' => $city_id,
                                ':cf_id' => $cf_id,
                                ':cd_value' => $value,
                            ]
                        );
                    }
                    $this->buildModelPageCities()->updateByPk(
                        $city_id,
                        [
                            'pc_lastup_user' => $uid,
                        ]
                    );
                    echo 'ok';
                    break;
            }
        } elseif (isset($_GET['id'])) {
            $this->db->sql = "SELECT cf_title, cd_value
                                FROM $dbcd cd
                                    LEFT JOIN $dbcf cf ON cf.cf_id = cd.cd_cf_id
                                WHERE cd.cd_pc_id = :id
                                    AND cd.cd_value != ''
                                    AND cf.cf_active = 1
                                ORDER BY cf_order";
            $this->db->execute(
                [
                    ':id' => (int) $_GET['id'],
                ]
            );
            $metas = $this->db->fetchAll();

            header('Content-Type: text/html; charset=utf-8');
            $this->templateEngine->displayPage('city/meta.tpl', ['metas' => $metas]);
        } else {
            throw new NotFoundException();
        }
        exit();
    }

    /**
     ************************************** РЕДАКТИРОВАНИЕ *****************
     * @param SiteResponse $response
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws RedirectException
     */
    private function detailCity(SiteResponse $response): void
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        if (!isset($_GET['city_id'])) {
            throw new NotFoundException();
        }
        $cityId = isset($_GET['city_id']) ? (int) $_GET['city_id'] : 0;
        if ($cityId === 0) {
            throw new NotFoundException();
        }

        $dbcd = $this->db->getTableName('city_data');
        $dbcf = $this->db->getTableName('city_fields');

        if (isset($_POST) && !empty($_POST)) {
            $this->buildModelPageCities()->updateByPk(
                $cityId,
                [
                    'pc_keywords' => $_POST['keywds'],
                    'pc_description' => $_POST['descr'],
                    'pc_announcement' => $_POST['anons'],
                    'pc_latitude' => $_POST['latitude'],
                    'pc_longitude' => $_POST['longitude'],
                    'pc_osm_id' => (int) $_POST['osm_id'],
                    'pc_inwheretext' => $_POST['inwhere'],
                    'pc_title_synonym' => $_POST['synonym'],
                    'pc_title_english' => $_POST['title_eng'],
                    'pc_title_translit' => $_POST['translit'],
                    'pc_website' => $_POST['web'],
                    'pc_coverphoto_id' => (int) $_POST['photo_id'],
                    'pc_lastup_user' => $this->webUser->getId(),
                ]
            );
            $city = $this->buildModelPageCities()->getItemByPk($cityId);

            throw new RedirectException($city['url']);
        }

        $citypage = $this->buildModelPageCities()->getItemByPk($cityId);
        $photos = $this->buildModelPhotos()->getItemsByRegion($cityId);

        $this->db->sql = "SELECT *
                    FROM $dbcd cd
                        LEFT JOIN $dbcf cf ON cf.cf_id = cd.cd_cf_id
                    WHERE cd.cd_pc_id = :city_id
                    ORDER BY cf_order";
        $this->db->execute(
            [
                ':city_id' => $cityId,
            ]
        );
        $meta = $this->db->fetchAll();

        $this->db->sql = "SELECT *
                    FROM $dbcf
                    WHERE cf_id NOT IN (SELECT cd_cf_id FROM $dbcd WHERE cd_pc_id = :pc_id)
                    ORDER BY cf_order";
        $this->db->execute(
            [
                ':pc_id' => $cityId,
            ]
        );
        $ref_meta = $this->db->fetchAll();

        $wordstat = $this->buildWordstatRepository()->getDataByCityName($citypage['pc_title']);

        $response->setLastEditTimestamp($citypage['last_update']);

        $body = $this->templateEngine->getContent('city/details.tpl', [
            'adminlogined' => $this->webUser->getId() ?: 0,
            'city' => $citypage,
            'baseurl' => GLOBAL_URL_ROOT,
            'meta' => $meta,
            'photos' => $photos['items'],
            'ref_meta' => $ref_meta,
            'wordstat' => $wordstat,
        ]);

        $response->getContent()->setBody($body);
    }

    /**
     ************************************** ДОБАВЛЕНИЕ *****************
     * @param SiteResponse $response
     * @throws RedirectException
     */
    private function addCity(SiteResponse $response): void
    {
        $newcity = '';
        $inBase = [];
        $already = [];

        if (isset($_POST) && !empty($_POST)) {
            $cid = $this->buildModelPageCities()->insert(
                [
                    'pc_title' => $_POST['city_name'],
                    'pc_city_id' => $_POST['city_id'],
                    'pc_region_id' => $_POST['region_id'],
                    'pc_country_id' => $_POST['country_id'],
                    'pc_country_code' => $_POST['country_code'],
                    'pc_latitude' => $_POST['latitude'],
                    'pc_longitude' => $_POST['longitude'],
                    'pc_rank' => 0,
                    'pc_title_translit' => Strings::getTransliteration($_POST['city_name']),
                    'pc_title_english' => Strings::getTransliteration($_POST['city_name']),
                    'pc_inwheretext' => $_POST['city_name'],
                    'pc_add_user' => $this->webUser->getId(),
                ]
            );
            if ($cid > 0) {
                throw new RedirectException('/city/detail/?city_id=' . $cid);
            }
        } elseif (!empty($_GET['cityname'])) {
            $newcity = trim($_GET['cityname']);
            $dbc = $this->db->getTableName('pagecity');
            $dbu = $this->db->getTableName('region_url');
            $dbrc = $this->db->getTableName('ref_city');
            $dbrr = $this->db->getTableName('ref_region');
            $dbrs = $this->db->getTableName('ref_country');
            $dbll = $this->db->getTableName('ref_citylatlon');
            //------------------- поиск уже имеющихся --------------
            $this->db->sql = "SELECT url.url, city.pc_title
                        FROM $dbc city
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE city.pc_title LIKE :newcity1 OR city.pc_title_synonym LIKE :newcity2";
            $this->db->execute(
                [
                    ':newcity1' => '%' . $newcity . '%',
                    ':newcity2' => '%' . $newcity . '%',
                ]
            );
            while ($row = $this->db->fetch()) {
                $already[$row['url']] = $row['pc_title'];
            }
            //------------------- поиск в справочнике регионов --------------
            $this->db->sql = "SELECT rc.name as name, rc.id as city_id,
                            rr.name as region, rr.id as region_id,
                            rs.name as country, rs.id as country_id, rs.alpha2 AS country_code,
                            city.pc_title as pc_title, url.url
                        FROM $dbrc rc
                        LEFT JOIN $dbrr rr ON rr.id = rc.region_id
                        LEFT JOIN $dbrs rs ON rs.id = rc.country_id
                        LEFT JOIN $dbc city ON city.pc_city_id = rc.id
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rc.name LIKE :newcity1
                        
                        UNION
                        
                        SELECT '' as name, 0 as city_id,
                            rr.name as region, rr.id as region_id,
                            rs.name as country, rs.id as country_id, rs.alpha2 AS country_code,
                            city.pc_title as pc_title, url.url
                        FROM $dbrr rr
                        LEFT JOIN $dbrs rs ON rs.id = rr.country_id
                        LEFT JOIN $dbc city ON city.pc_region_id = rr.id AND city.pc_city_id = 0
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rr.name LIKE :newcity2
                        
                        UNION
                        
                        SELECT '' as name, 0 as city_id,
                            '' as region, 0 as region_id,
                            rs.name as country, rs.id as country_id, rs.alpha2 AS country_code,
                            city.pc_title as pc_title, url.url
                        FROM $dbrs rs
                        LEFT JOIN $dbc city ON city.pc_country_id = rs.id AND city.pc_city_id = 0 AND city.pc_region_id = 0
                        LEFT JOIN $dbu url ON url.uid = city.pc_url_id
                        WHERE rs.name LIKE :newcity3
                        
                        ORDER BY country, region, name";
            $this->db->execute(
                [
                    ':newcity1' => '%' . $newcity . '%',
                    ':newcity2' => '%' . $newcity . '%',
                    ':newcity3' => '%' . $newcity . '%',
                ]
            );
            while ($row = $this->db->fetch()) {
                $inBase[] = $row;
            }
            foreach ($inBase as $id => $city) {
                $translit = Strings::getTransliteration($city['name']);
                $inBase[$id]['translit'] = $translit;
                $this->db->sql = "SELECT * FROM $dbll WHERE LOWER(ll_name) = LOWER(:name) LIMIT 1";
                $state = $this->db->execute(
                    [
                        ':name' => $translit,
                    ]
                );
                $inBase[$id]['latlon'] = null;
                if ($state) {
                    $row = $this->db->fetch();
                    if ($row === null) {
                        continue;
                    }
                    $inBase[$id]['lat'] = $row['ll_lat'];
                    $inBase[$id]['lon'] = $row['ll_lon'];
                    $latitude = $row['ll_lat'] >= 0 ? 'N' : 'S';
                    $latitude .= abs($row['ll_lat']);
                    $longitude = $row['ll_lon'] >= 0 ? 'E' : 'W';
                    $longitude .= abs($row['ll_lon']);
                    if ($latitude !== 'N0' && $longitude !== 'E0') {
                        $inBase[$id]['latlon'] = "{$row['ll_name']}: $latitude, $longitude";
                    }
                }
            }

            //-------------------------------------------------------------------
        }

        $this->templateEngine->assign('inbase', $inBase);
        $this->templateEngine->assign('addregion', $newcity);
        $this->templateEngine->assign('already', $already);
        $this->templateEngine->assign('freeplace', mb_strlen($newcity) >= 5 ? $newcity : null);
        $this->templateEngine->assign('adminlogined', $this->webUser->getId());
        $response->getContent()->setBody($this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/city/add.tpl'));
    }

    /**
     ************************************** СПИСОК *********************
     * @param SiteResponse $response
     */
    private function pageCity(SiteResponse $response): void
    {
        $dbc = $this->db->getTableName('pagecity');
        $dbr = $this->db->getTableName('region_url');
        $dbrc = $this->db->getTableName('ref_country');
        $dbrr = $this->db->getTableName('ref_region');
        $dbws = $this->db->getTableName('wordstat');
        $where = (!$this->webUser->isEditor()) ? "WHERE city.pc_text is not null" : '';
        $this->db->sql = "SELECT city.pc_id, city.pc_title, city.pc_latitude, city.pc_longitude,
                            city.pc_city_id, city.pc_region_id, city.pc_country_id,
                            url.url,
                            CHAR_LENGTH(city.pc_text) as len,
                            CHAR_LENGTH(city.pc_announcement) as anons_len,
                            city.pc_inwheretext,
                            city.pc_pagepath,
                            city.pc_count_points,
                            city.pc_count_metas,
                            city.pc_count_photos,
                            ws.ws_weight_max, ws.ws_position,
                            UNIX_TIMESTAMP(city.pc_lastup_date) AS last_update
                    FROM $dbc city
                        LEFT JOIN $dbr url ON url.uid = city.pc_url_id
                        LEFT JOIN $dbrc rc ON rc.id = city.pc_country_id
                        LEFT JOIN $dbrr rr ON rr.id = city.pc_region_id
                        LEFT JOIN $dbws ws ON ws.ws_city_id = city.pc_city_id AND ws.ws_city_title = city.pc_title
                $where
                    GROUP BY city.pc_id
                    ORDER BY rc.ordering, rc.name, rr.ordering, rr.name, url.url, city.pc_title";
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            $row['pc_pagepath'] = strip_tags($row['pc_pagepath'] ?? '');
            $response->setMaxLastEditTimestamp($row['last_update']);
            $cities[] = $row;
        }

        $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), 'https://culttourism.ru/data/images/pages/map-example-500.png');

        $this->templateEngine->assign('tcity', $cities);
        $this->templateEngine->assign('adminlogined', $this->webUser->getId() ?? 0);

        if ($this->webUser->isEditor()) {
            $response->getContent()->setBody($this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/city/city.edit.tpl'));
        } else {
            $response->getContent()->setBody($this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/city/city.show.tpl'));
        }
    }

    /**
     * @return MPhotos
     */
    private function buildModelPhotos(): MPhotos
    {
        if ($this->modelPhotos === null) {
            $this->modelPhotos = new MPhotos($this->db);
        }

        return $this->modelPhotos;
    }

    /**
     * @return MPageCities
     */
    private function buildModelPageCities(): MPageCities
    {
        if ($this->modelPageCities === null) {
            $this->modelPageCities = new MPageCities($this->db);
        }

        return $this->modelPageCities;
    }

    private function buildWordstatRepository(): WordstatRepository
    {
        if ($this->wordstatRepository === null) {
            $this->wordstatRepository = new WordstatRepository($this->db);
        }

        return $this->wordstatRepository;
    }

    private function getWeatherService(): WeatherService
    {
        if ($this->weatherService === null) {
            $this->weatherService = WeatherFactory::build($this->globalConfig->getOpenWeatherAPIKey());
        }

        return $this->weatherService;
    }
}
