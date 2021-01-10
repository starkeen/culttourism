<?php

declare(strict_types=1);

namespace app\modules;

use app\constant\OgType;
use app\core\exception\RoutingException;
use app\core\GlobalConfig;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;
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
    private const DESCRIPTION_THRESHOLD = 300; // обрезаем описание в мета-тегах до этой величины

    private const REDIRECT_BY_ID_MAX = 17000; // редирект с урлов в старом формате не выше этого идентификатора

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
     * @throws RedirectException
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        $this->processPageByURL($request, $response);

        if ($response->getContent()->getBody() === '') {
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
     * @throws NotFoundException
     * @throws RedirectException
     */
    public function processPageByURL(SiteRequest $request, SiteResponse $response): void
    {
        $url = $request->getUrl();
        if ($url !== '') {
            $response->getContent()->getHead()->addTitleElement($this->globalConfig->getDefaultPageTitle());
            $regs = [];
            $urlPartsArray = !empty($url) ? explode('/', $url) : [];
            $urlParts = array_pop($urlPartsArray);
            if ($urlParts === 'map.html') {
                $this->redirectToCityMap($url);
            } elseif ($urlParts === 'index.html') {
                $url = substr($url, 0, stripos($url, $urlParts));
                throw new RedirectException($url);
            } elseif (in_array($urlParts, self::REDIRECT_SUFFIXES, true)) {
                $url = substr($url, 0, stripos($url, $urlParts));
                throw new RedirectException($url);
            } elseif (preg_match('/object(\d+)\.html/i', $urlParts, $regs)) {
                $objectId = (int) $regs[1];
                if ($objectId > 0 && $objectId < self::REDIRECT_BY_ID_MAX) {
                    $objectCanonical = $this->getObjectCanonicalById($objectId);
                    if ($objectCanonical !== null) {
                        throw new RedirectException($objectCanonical);
                    }
                }
                throw new NotFoundException();
            } elseif (preg_match('/([a-z0-9_-]+)\.html/i', $urlParts, $regs)) {
                $objectCanonical = $regs[1];
                $body = $this->getPageObjectBySlug($objectCanonical, $response);
                $response->getContent()->setBody($body);
            } else {
                $body = $this->getPageCity($url, $response);
                $response->getContent()->setBody($body);
            }
        } else {
            throw new RoutingException('Ошибка в роутинге городов и объектов');
        }
    }

    /**
     * @param int $id
     * @return string|null
     */
    private function getObjectCanonicalById(int $id): ?string
    {
        $object = $this->getModelPagePoints()->getItemByPk($id);
        if ($object !== null) {
            return $object['url_canonical'];
        }
        return null;
    }

    /**
     * @param string $slugLine
     * @param SiteResponse $response
     * @return string
     * @throws NotFoundException
     * @throws RedirectException
     */
    private function getPageObjectBySlug(string $slugLine, SiteResponse $response): string
    {
        if ($slugLine === '') {
            throw new NotFoundException();
        }

        $objects = $this->getModelPagePoints()->searchSlugline($slugLine);
        if (!isset($objects[0])) {
            throw new NotFoundException();
        }
        $object = $objects[0];
        $response->getContent()->getHead()->setCanonicalUrl($object['url_canonical']);
        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== $object['url_canonical']) {
            throw new RedirectException($object['url_canonical']);
        }

        $city = $this->getModelPageCities()->getItemByPk($object['pt_citypage_id']);

        $shortDescription = strip_tags($object['pt_description']);
        $short = $shortDescription;
        if (mb_strlen($shortDescription) >= self::DESCRIPTION_THRESHOLD) {
            $position = mb_strpos($shortDescription, ' ', self::DESCRIPTION_THRESHOLD) ?: self::DESCRIPTION_THRESHOLD;
            $short = mb_substr($shortDescription, 0, $position, 'utf-8');
        }
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
        $this->getModelStatPoints()->add($object['pt_id'], $this->user->getHash());

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
        $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $object['esc_name']);
        $response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $short);
        $response->getContent()->getHead()->addOGMeta(OgType::UPDATED_TIME(), (string) $response->getLastEditTimestamp());
        $objImage = null;
        if ((int) $object['pt_photo_id'] !== 0) {
            $photo = $this->getModelPhotos()->getItemByPk($object['pt_photo_id']);
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

        return $this->templateEngine->getContent(
            '_pages/pagepoint.tpl',
            [
                'object' => $object,
                'city' => $city,
                'page_image' => $objImage,
                'lists' => $this->getModelListItems()->getListsForPointId((int) $object['pt_id']),
            ]
        );
    }

    /**
     * @param string $url
     * @param SiteResponse $response
     * @return string
     * @throws NotFoundException
     * @throws RedirectException
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
            $location = str_replace('index.html', '', $url);
            throw new RedirectException($location);
        }

        $row = $this->getModelPageCities()->getCityByUrl($urlFiltered);

        if (!empty($row) && isset($row['pc_title']) && $row['pc_title'] != '') {
            $row['pc_zoom'] = ($row['pc_latlon_zoom']) ?: 12;
            $response->setLastEditTimestamp($row['last_update']);

            //--------------------  c a n o n i c a l  ------------------------
            $response->getContent()->getHead()->setCanonicalUrl($row['url_canonical']);
            if ($row['url_canonical'] !== ($url . '/')) {
                throw new RedirectException($row['url_canonical']);
            }

            $points_data = $this->getModelPagePoints()->getPointsByCity($row['pc_id'], $this->user->isEditor());

            $response->setMaxLastEditTimestamp($points_data['last_update']);
            if ($this->user->isEditor()) {
                $response->setLastEditTimestamp(0);
            }

            $this->getModelStatCity()->add($row['pc_id'], $this->user->getHash());

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
            $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), 'Достопримечательности ' . $row['pc_inwheretext']);
            $response->getContent()->getHead()->addOGMeta(
                OgType::DESCRIPTION(),
                $row['pc_description'] . ($row['pc_announcement'] ? '. ' . $row['pc_announcement'] : '')
            );
            $response->getContent()->getHead()->addOGMeta(OgType::UPDATED_TIME(), (string) $response->getLastEditTimestamp());
            if ($row['pc_coverphoto_id']) {
                $photo = $this->getModelPhotos()->getItemByPk($row['pc_coverphoto_id']);
                $cityImage = Urls::getAbsoluteURL($photo['ph_src']);
                $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $cityImage);
            } else {
                $cityImage = null;
            }

            $response->getContent()->setCustomJsModule('city');
            $template = $this->user->isEditor() ? '_pages/pagecity.edit.tpl' : '_pages/pagecity.show.tpl';
            return $this->templateEngine->getContent(
                $template,
                [
                    'city' => $row,
                    'points' => $points_data['points'],
                    'points_sight' => $points_data['points_sight'],
                    'points_servo' => $points_data['points_service'],
                    'page_url' => GLOBAL_URL_ROOT,
                    'page_image' => $cityImage,
                    'types_select' => $points_data['types'],
                    'ptypes' => [],
                ]
            );
        }

        throw new NotFoundException();
    }

    /**
     * @param string $url
     * @throws RedirectException
     */
    private function redirectToCityMap(string $url): void
    {
        $city = $this->getModelPageCities()->getCityByUrl(str_replace('/map.html', '', $url));
        $location = "/map/#center={$city['pc_longitude']},{$city['pc_latitude']}&zoom={$city['pc_latlon_zoom']}";
        throw new RedirectException($location);
    }

    /**
     * @return MPhotos
     */
    private function getModelPhotos(): MPhotos
    {
        return new MPhotos($this->db);
    }

    /**
     * @return MPageCities
     */
    private function getModelPageCities(): MPageCities
    {
        return new MPageCities($this->db);
    }

    /**
     * @return MPagePoints
     */
    private function getModelPagePoints(): MPagePoints
    {
        return new MPagePoints($this->db);
    }

    /**
     * @return MListsItems
     */
    private function getModelListItems(): MListsItems
    {
        return new MListsItems($this->db);
    }

    /**
     * @return MStatcity
     */
    private function getModelStatCity(): MStatcity
    {
        return new MStatcity($this->db);
    }

    /**
     * @return MStatpoints
     */
    private function getModelStatPoints(): MStatpoints
    {
        return new MStatpoints($this->db);
    }
}
