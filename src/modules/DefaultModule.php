<?php

declare(strict_types=1);

namespace app\modules;

use app\constant\OgType;
use app\core\GlobalConfig;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\sys\Logger;
use app\sys\TemplateEngine;
use app\utils\Urls;
use MListsItems;
use MPageCities;
use MPagePoints;
use MPhotos;
use MStatcity;
use MStatpoints;
use RuntimeException;

class DefaultModule implements ModuleInterface
{
    private const DESCRIPTION_THRESHOLD = 200;

    private const REDIRECT_SUFFIXES = [
        'undefined',
        'com.google.android.googlequicksearchbox',
        'android-app%3A',
    ];

    /**
     * @var MyDB
     */
    private $db;

    /**
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @var TemplateEngine
     */
    private $templateEngine;

    /**
     * @var WebUser
     */
    private $user;

    /**
     * @param MyDB $db
     * @param TemplateEngine $templateEngine
     * @param WebUser $user
     * @param GlobalConfig $globalConfig
     */
    public function __construct(MyDB $db, TemplateEngine $templateEngine, WebUser $user, GlobalConfig $globalConfig)
    {
        $this->db = $db;
        $this->globalConfig = $globalConfig;
        $this->templateEngine = $templateEngine;
        $this->user = $user;
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        $this->user->getAuth()->checkSession('web');

        $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $response->getContent()->getHead()->getTitle());
        $response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $response->getContent()->getHead()->getDescription());
        $response->getContent()->getHead()->addOGMeta(OgType::SITE_NAME(), $this->globalConfig->getDefaultPageTitle());
        $response->getContent()->getHead()->addOGMeta(OgType::LOCALE(), 'ru_RU');
        $response->getContent()->getHead()->addOGMeta(OgType::TYPE(), 'website');
        $response->getContent()->getHead()->addOGMeta(OgType::URL(), Urls::getAbsoluteURL($_SERVER['REQUEST_URI']));
        $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), _SITE_URL . 'img/logo/culttourism-head.jpg');
        $response->getContent()->getHead()->addMicroData('image', _SITE_URL . 'img/logo/culttourism-head.jpg');

        if (!$response->getContent()->getBody()) {
            $body = $this->getPageByURL($request, $response);
            $response->getContent()->setBody($body);
        }
        if (!$response->getContent()->getBody()) {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return true;
    }


    /**
     * @param SiteRequest $request
     * @param SiteResponse $response
     * @return bool|string|void
     * @throws NotFoundException
     */
    public function getPageByURL(SiteRequest $request, SiteResponse $response)
    {
        $url = $request->getUrl();
        if ($url !== '') {
            $response->getContent()->getHead()->addTitleElement($this->globalConfig->getDefaultPageTitle());

            $regs = [];
            $url_parts_array = !empty($url) ? explode('/', $url) : [];
            $urlParts = array_pop($url_parts_array);
            if ($urlParts === 'map.html') {
                $this->showPageMap($url, $response);
            } elseif ($urlParts === 'index.html') {
                return $this->getPageCity($url, $response);
            } elseif (in_array($urlParts, self::REDIRECT_SUFFIXES, true)) {
                $url = substr($url, 0, stripos($url, $urlParts));
                $response->getHeaders()->sendRedirect($url, true);
            } elseif (preg_match('/object(\d+)\.html/i', $urlParts, $regs)) {
                throw new NotFoundException();
            } elseif (preg_match('/([a-z0-9_-]+)\.html/i', $urlParts, $regs)) {
                return $this->getPageObjectBySlug($regs[1], $response);
            } else {
                return $this->getPageCity($url, $response);
            }
        } else {
            throw new RuntimeException('Ошибка в роутинге городов и объектов');
        }
    }

    /**
     * @param string $slugLine
     * @param SiteResponse $response
     * @return string
     */
    private function getPageObjectBySlug(string $slugLine, SiteResponse $response)
    {
        if (!$slugLine) {
            return '';
        }

        $pts = new MPagePoints($this->db);
        $pcs = new MPageCities($this->db);
        $li = new MListsItems($this->db);

        $objects = $pts->searchSlugline($slugLine);
        $object = $objects[0] ?? false;
        if (!$object) {
            return false;
        }
        $response->getContent()->getHead()->setCanonicalUrl($object['url_canonical']);
        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== $object['url_canonical']) {
            $response->getHeaders()->sendRedirect($object['url_canonical'], true);
        }

        $city = $pcs->getItemByPk($object['pt_citypage_id']);

        $shortDescr = strip_tags($object['pt_description']);
        $short = mb_strlen($shortDescr) >= self::DESCRIPTION_THRESHOLD ? mb_substr(
            $shortDescr,
            0,
            mb_strpos(
                $shortDescr,
                ' ',
                self::DESCRIPTION_THRESHOLD
            ),
            'utf-8'
        ) : $shortDescr;
        $object['esc_name'] = htmlentities($object['pt_name'], ENT_QUOTES, 'utf-8');
        $object['map_zoom'] = $object['pt_latlon_zoom'] ?: 14;
        if ($object['pt_latitude'] && $object['pt_longitude']) {
            $object_lat_short = round($object['pt_latitude'], 5);
            $object_lon_short = round($object['pt_longitude'], 5);
            $object['gps_dec'] = (($object_lat_short >= 0) ? 'N' : 'S') . abs(
                    $object_lat_short
                ) . ' ' . (($object_lon_short >= 0) ? 'E' : 'W') . abs($object_lon_short);
            $object['sw_ne_delta'] = 0.01;
            $object['sw_ne'] = [
                'sw' => [
                    'lat' => $object['pt_latitude'] - $object['sw_ne_delta'],
                    'lon' => $object['pt_longitude'] - $object['sw_ne_delta']
                ],
                'ne' => [
                    'lat' => $object['pt_latitude'] + $object['sw_ne_delta'],
                    'lon' => $object['pt_longitude'] + $object['sw_ne_delta']
                ],
            ];
        }

        $response->setLastEditTimestamp($object['last_update']);

        //------------------  s t a t i s t i c s  ------------------------
        $sp = new MStatpoints($this->db);
        $sp->add($object['pt_id'], $this->user->getHash());

        $response->getContent()->getHead()->addTitleElement($city['pc_title_unique']);
        $response->getContent()->getHead()->addTitleElement($object['esc_name']);

        $response->getContent()->getHead()->addCustomMeta('business:contact_data:locality', $city['pc_title_unique']);

        $response->getContent()->getHead()->addMicroData('@type', 'Place');
        $response->getContent()->getHead()->addMicroData('name', $object['esc_name']);
        $response->getContent()->getHead()->addMicroData('description', $short);
        $response->getContent()->getHead()->addMicroData('address', [
            '@type' => 'PostalAddress',
            'addressLocality' => $city['pc_title_unique'],
        ]);

        if ($object['tr_sight']) {
            $response->getContent()->getHead()->addDescription('Достопримечательности ' . $city['pc_inwheretext']);
        }
        if (!empty($object['pt_latitude']) && !empty($object['pt_longitude'])) {
            $response->getContent()->getHead()->addDescription('GPS-координаты');
            $response->getContent()->getHead()->addCustomMeta('place:location:latitude', (string) $object['pt_latitude']);
            $response->getContent()->getHead()->addCustomMeta('place:location:longitude', (string) $object['pt_longitude']);
            $response->getContent()->getHead()->addMicroData(
                'geo',
                [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $object['pt_latitude'],
                    'longitude' => $object['pt_longitude'],
                ]
            );
        }
        $response->getContent()->getHead()->addDescription("{$object['tp_short']} {$city['pc_inwheretext']}");
        $response->getContent()->getHead()->addDescription($object['esc_name']);
        $response->getContent()->getHead()->addDescription($short);
        $response->getContent()->getHead()->addKeyword($city['pc_title']);
        $response->getContent()->getHead()->addKeyword($object['esc_name']);
        if (isset($object['gps_dec'])) {
            $response->getContent()->getHead()->addKeyword('координаты GPS');
        }

        $response->getContent()->getHead()->addOGMeta(OgType::TYPE(), 'article');
        $response->getContent()->getHead()->addOGMeta(OgType::URL(), $response->getContent()->getHead()->getCanonicalUrl());
        $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $object['esc_name']);
        $response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $short);
        $response->getContent()->getHead()->addOGMeta(OgType::UPDATED_TIME(), (string) $response->getLastEditTimestamp());
        $objImage = null;
        if ((int) $object['pt_photo_id'] !== 0) {
            $ph = new MPhotos($this->db);
            $photo = $ph->getItemByPk($object['pt_photo_id']);
            $objImage = Urls::getAbsoluteURL($photo['ph_src']);
            $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $objImage);
        }

        if (!empty($object['pt_website'])) {
            $response->getContent()->getHead()->addCustomMeta('business:contact_data:website', $object['pt_website']);
            $response->getContent()->getHead()->addMicroData('url', $object['pt_website']);
        }
        if (!empty($object['pt_phone'])) {
            $response->getContent()->getHead()->addCustomMeta('business:contact_data:phone_number', $object['pt_phone']);
            $response->getContent()->getHead()->addMicroData('telephone', $object['pt_phone']);
        }
        if (!empty($object['pt_worktime'])) {
            $response->getContent()->getHead()->addMicroData('openingHours', $object['pt_worktime']);
        }

        $response->getContent()->setCustomJsModule('point');

        $this->templateEngine->assign('object', $object);
        $this->templateEngine->assign('city', $city);
        $this->templateEngine->assign('page_image', $objImage);
        $this->templateEngine->assign('lists', $li->getListsForPointId($object['pt_id']));

        return $this->templateEngine->fetch(_DIR_TEMPLATES . '/_pages/pagepoint.tpl');
    }

    /**
     * @param string $url
     * @param SiteResponse $response
     * @return string
     * @throws NotFoundException
     */
    private function getPageCity(string $url, SiteResponse $response): string
    {
        $urlParts = explode('/', $url);
        $urlFiltered = array_map(
            static function ($uItem) {
                return trim(str_replace('+', ' ', $uItem));
            },
            $urlParts
        );
        $urlFiltered = '/' . implode('/', array_filter($urlFiltered));
        $lastPart = array_pop($urlParts);
        if ($lastPart === 'index.html') {
            $response->getHeaders()->sendRedirect(str_replace('index.html', '', $url), true);
        }

        $pcs = new MPageCities($this->db);
        $pts = new MPagePoints($this->db);
        $row = $pcs->getCityByUrl($urlFiltered);

        if (!empty($row) && isset($row['pc_title']) && $row['pc_title'] != '') {
            $row['pc_zoom'] = ($row['pc_latlon_zoom']) ?: 12;
            $response->setLastEditTimestamp($row['last_update']);

            //--------------------  c a n o n i c a l  ------------------------
            $response->getContent()->getHead()->setCanonicalUrl($row['url_canonical']);
            if ($row['url_canonical'] !== ($url . '/')) {
                $response->getHeaders()->sendRedirect($row['url_canonical']);
            }

            $points_data = $pts->getPointsByCity($row['pc_id'], $this->user->isEditor());

            $response->setMaxLastEditTimestamp($points_data['last_update']);
            if ($this->user->isEditor()) {
                $response->setLastEditTimestamp(0);
            }

            $sc = new MStatcity($this->db);
            $sc->add($row['pc_id'], $this->user->getHash());

            $response->getContent()->getHead()->addTitleElement($row['pc_title_unique'] . ': достопримечательности');
            $response->getContent()->getHead()->addDescription($row['pc_title_unique'] . ' - что посмотреть');
            if ($row['pc_description']) {
                $response->getContent()->getHead()->addDescription($row['pc_description']);
            }
            $response->getContent()->getHead()->addDescription('Достопримечательности ' . $row['pc_inwheretext'] . ' с GPS-координатами');
            if ($row['pc_keywords']) {
                $response->getContent()->getHead()->addKeyword($row['pc_keywords']);
            }
            $response->getContent()->getHead()->addKeyword('достопримечательности ' . $row['pc_inwheretext']);
            $response->getContent()->getHead()->addKeyword('Координаты GPS');
            $response->getContent()->getHead()->addKeyword($row['pc_title_translit']);
            if ($row['pc_title_english'] && $row['pc_title_english'] !== $row['pc_title_translit']) {
                $response->getContent()->getHead()->addKeyword($row['pc_title_english']);
            }
            if ($row['pc_title_synonym']) {
                $response->getContent()->getHead()->addKeyword($row['pc_title_synonym']);
            }

            $response->getContent()->getHead()->addOGMeta(OgType::TYPE(), 'article');
            $response->getContent()->getHead()->addOGMeta(OgType::URL(), $response->getContent()->getHead()->getCanonicalUrl());
            $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), 'Достопримечательности ' . $row['pc_inwheretext']);
            $response->getContent()->getHead()->addOGMeta(
                OgType::DESCRIPTION(),
                $row['pc_description'] . ($row['pc_announcement'] ? '. ' . $row['pc_announcement'] : '')
            );
            $response->getContent()->getHead()->addOGMeta(OgType::UPDATED_TIME(), (string) $response->getLastEditTimestamp());
            if ($row['pc_coverphoto_id']) {
                $ph = new MPhotos($this->db);
                $photo = $ph->getItemByPk($row['pc_coverphoto_id']);
                $cityImage = Urls::getAbsoluteURL($photo['ph_src']);
                $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $cityImage);
            } else {
                $cityImage = null;
            }

            $this->templateEngine->assign('city', $row);
            $this->templateEngine->assign('points', $points_data['points']);
            $this->templateEngine->assign('points_sight', $points_data['points_sight']);
            $this->templateEngine->assign('points_servo', $points_data['points_service']);
            $this->templateEngine->assign('page_url', _URL_ROOT);
            $this->templateEngine->assign('page_image', $cityImage);
            $this->templateEngine->assign('types_select', $points_data['types']);
            $this->templateEngine->assign('ptypes', []);
            $response->getContent()->setCustomJsModule('city');

            if ($this->user->isEditor()) {
                return $this->templateEngine->fetch(_DIR_TEMPLATES . '/_pages/pagecity.edit.tpl');
            } else {
                return $this->templateEngine->fetch(_DIR_TEMPLATES . '/_pages/pagecity.show.tpl');
            }
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @param string $url
     * @param SiteResponse $response
     */
    private function showPageMap(string $url, SiteResponse $response): void
    {
        $pc = new MPageCities($this->db);
        $city = $pc->getCityByUrl(str_replace('/map.html', '', $url));
        $location = "/map/#center={$city['pc_longitude']},{$city['pc_latitude']}&zoom={$city['pc_latlon_zoom']}";
        $response->getHeaders()->sendRedirect($location, true);
    }
}
