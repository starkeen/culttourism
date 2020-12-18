<?php

use app\constant\OgType;
use app\core\SiteRequest;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\utils\Urls;

class Page extends Core
{
    private const DESCRIPTION_THRESHOLD = 200;

    private const REDIRECT_SUFFIXES = [
        'undefined',
        'com.google.android.googlequicksearchbox',
        'android-app%3A',
    ];

    /**
     * @inheritDoc
     * @throws NotFoundException
     */
    public function compileContent(): void
    {
        if (!$this->pageContent->getBody()) {
            $this->pageContent->setBody($this->getPageByURL($this->siteRequest));
        }
        if (!$this->pageContent->getBody()) {
            throw new NotFoundException();
        }
    }

    /**
     * @param SiteRequest $request
     *
     * @return bool|string|void
     * @throws NotFoundException
     */
    public function getPageByURL(SiteRequest $request)
    {
        $url = $request->getUrl();
        if ($url !== '') {
            $this->checkRedirect($url);

            $this->pageContent->getHead()->addTitleElement($this->globalConfig->getDefaultPageTitle());

            $regs = [];
            $url_parts_array = !empty($url) ? explode('/', $url) : [];
            $urlParts = array_pop($url_parts_array);
            if ($urlParts === 'map.html') {
                $this->showPageMap($url);
            } elseif ($urlParts === 'index.html') {
                return $this->getPageCity($url);
            } elseif (in_array($urlParts, self::REDIRECT_SUFFIXES, true)) {
                $url = substr($url, 0, stripos($url, $urlParts));
                $this->pageHeaders->add('HTTP/1.1 301 Moved Permanently');
                $this->pageHeaders->add('Location: ' . $url);
                $this->pageHeaders->flush();
                exit();
            } elseif (preg_match('/object(\d+)\.html/i', $urlParts, $regs)) {
                throw new NotFoundException();
            } elseif (preg_match('/([a-z0-9_-]+)\.html/i', $urlParts, $regs)) {
                return $this->getPageObjectBySlug($regs[1]);
            } else {
                return $this->getPageCity($url);
            }
        } else {
            throw new RuntimeException('Ошибка в роутинге городов и объектов');
        }
    }

    /**
     * @param string $slugLine
     *
     * @return string
     */
    private function getPageObjectBySlug(string $slugLine)
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
        $this->pageContent->getHead()->setCanonicalUrl($object['url_canonical']);
        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== $object['url_canonical']) {
            $this->pageHeaders->add('HTTP/1.1 301 Moved Permanently');
            $this->pageHeaders->add('Location: ' . $object['url_canonical']);
            $this->pageHeaders->flush();
            exit();
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
            $object_lat_short = mb_substr($object['pt_latitude'], 0, 8);
            $object_lon_short = mb_substr($object['pt_longitude'], 0, 8);
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


        $this->lastedit_timestamp = $object['last_update'];
        $this->lastedit = gmdate('D, d M Y H:i:s', $this->lastedit_timestamp) . ' GMT';

        //------------------  s t a t i s t i c s  ------------------------
        $sp = new MStatpoints($this->db);
        $sp->add($object['pt_id'], $this->webUser->getHash());

        $this->pageContent->getHead()->addTitleElement($city['pc_title_unique']);
        $this->pageContent->getHead()->addTitleElement($object['esc_name']);

        $this->pageContent->getHead()->addCustomMeta('business:contact_data:locality', $city['pc_title_unique']);

        $this->pageContent->getHead()->addMicroData('@type', 'Place');
        $this->pageContent->getHead()->addMicroData('name', $object['esc_name']);
        $this->pageContent->getHead()->addMicroData('description', $short);
        $this->pageContent->getHead()->addMicroData('address', [
            '@type' => 'PostalAddress',
            'addressLocality' => $city['pc_title_unique'],
        ]);

        if ($object['tr_sight']) {
            $this->pageContent->getHead()->addDescription('Достопримечательности ' . $city['pc_inwheretext']);
        }
        if (!empty($object['pt_latitude']) && !empty($object['pt_longitude'])) {
            $this->pageContent->getHead()->addDescription('GPS-координаты');
            $this->pageContent->getHead()->addCustomMeta('place:location:latitude', $object['pt_latitude']);
            $this->pageContent->getHead()->addCustomMeta('place:location:longitude', $object['pt_longitude']);
            $this->pageContent->getHead()->addMicroData(
                'geo',
                [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $object['pt_latitude'],
                    'longitude' => $object['pt_longitude'],
                ]
            );
        }
        $this->pageContent->getHead()->addDescription("{$object['tp_short']} {$city['pc_inwheretext']}");
        $this->pageContent->getHead()->addDescription($object['esc_name']);
        $this->pageContent->getHead()->addDescription($short);
        $this->pageContent->getHead()->addKeyword($city['pc_title']);
        $this->pageContent->getHead()->addKeyword($object['esc_name']);
        if (isset($object['gps_dec'])) {
            $this->pageContent->getHead()->addKeyword('координаты GPS');
        }

        $this->pageContent->getHead()->addOGMeta(OgType::TYPE(), 'article');
        $this->pageContent->getHead()->addOGMeta(OgType::URL(), $this->pageContent->getHead()->getCanonicalUrl());
        $this->pageContent->getHead()->addOGMeta(OgType::TITLE(), $object['esc_name']);
        $this->pageContent->getHead()->addOGMeta(OgType::DESCRIPTION(), $short);
        $this->pageContent->getHead()->addOGMeta(OgType::UPDATED_TIME(), $this->lastedit_timestamp);
        $objImage = null;
        if ((int) $object['pt_photo_id'] !== 0) {
            $ph = new MPhotos($this->db);
            $photo = $ph->getItemByPk($object['pt_photo_id']);
            $objImage = Urls::getAbsoluteURL($photo['ph_src']);
            $this->pageContent->getHead()->addOGMeta(OgType::IMAGE(), $objImage);
        }

        if (!empty($object['pt_website'])) {
            $this->pageContent->getHead()->addCustomMeta('business:contact_data:website', $object['pt_website']);
            $this->pageContent->getHead()->addMicroData('url', $object['pt_website']);
        }
        if (!empty($object['pt_phone'])) {
            $this->pageContent->getHead()->addCustomMeta('business:contact_data:phone_number', $object['pt_phone']);
            $this->pageContent->getHead()->addMicroData('telephone', $object['pt_phone']);
        }
        if (!empty($object['pt_worktime'])) {
            $this->pageContent->getHead()->addMicroData('openingHours', $object['pt_worktime']);
        }

        $this->pageContent->setCustomJsModule('point');

        $this->smarty->assign('object', $object);
        $this->smarty->assign('city', $city);
        $this->smarty->assign('page_image', $objImage);
        $this->smarty->assign('lists', $li->getListsForPointId($object['pt_id']));

        return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/pagepoint.sm.html');
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws NotFoundException
     */
    private function getPageCity(string $url): string
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
            $this->pageHeaders->add('HTTP/1.1 301 Moved Permanently');
            $this->pageHeaders->add('Location: ' . str_replace('index.html', '', $url));
            $this->pageHeaders->flush();
            exit();
        }

        $pcs = new MPageCities($this->db);
        $pts = new MPagePoints($this->db);
        $row = $pcs->getCityByUrl($urlFiltered);

        if (!empty($row) && isset($row['pc_title']) && $row['pc_title'] != '') {
            $row['pc_zoom'] = ($row['pc_latlon_zoom']) ?: 12;
            $this->lastedit_timestamp = $row['last_update'];

            //--------------------  c a n o n i c a l  ------------------------
            $this->pageContent->getHead()->setCanonicalUrl($row['url_canonical']);
            if ($row['url_canonical'] !== ($url . '/')) {
                $this->pageHeaders->add('HTTP/1.1 301 Moved Permanently');
                $this->pageHeaders->add('Location: ' . $row['url_canonical']);
                $this->pageHeaders->flush();
                exit();
            }

            $points_data = $pts->getPointsByCity($row['pc_id'], $this->webUser->isEditor());

            if ($points_data['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $points_data['last_update'];
            }
            if ($this->webUser->isEditor()) {
                $this->lastedit_timestamp = 0;
            }

            $this->lastedit = gmdate('D, d M Y H:i:s', $this->lastedit_timestamp) . ' GMT';

            $sc = new MStatcity($this->db);
            $sc->add($row['pc_id'], $this->webUser->getHash());

            $this->pageContent->getHead()->addTitleElement($row['pc_title_unique'] . ': достопримечательности');
            $this->pageContent->getHead()->addDescription($row['pc_title_unique'] . ' - что посмотреть');
            if ($row['pc_description']) {
                $this->pageContent->getHead()->addDescription($row['pc_description']);
            }
            $this->pageContent->getHead()->addDescription('Достопримечательности ' . $row['pc_inwheretext'] . ' с GPS-координатами');
            if ($row['pc_keywords']) {
                $this->pageContent->getHead()->addKeyword($row['pc_keywords']);
            }
            $this->pageContent->getHead()->addKeyword('достопримечательности ' . $row['pc_inwheretext']);
            $this->pageContent->getHead()->addKeyword('Координаты GPS');
            $this->pageContent->getHead()->addKeyword($row['pc_title_translit']);
            if ($row['pc_title_english'] && $row['pc_title_english'] !== $row['pc_title_translit']) {
                $this->pageContent->getHead()->addKeyword($row['pc_title_english']);
            }
            if ($row['pc_title_synonym']) {
                $this->pageContent->getHead()->addKeyword($row['pc_title_synonym']);
            }

            $this->pageContent->getHead()->addOGMeta(OgType::TYPE(), 'article');
            $this->pageContent->getHead()->addOGMeta(OgType::URL(), $this->pageContent->getHead()->getCanonicalUrl());
            $this->pageContent->getHead()->addOGMeta(OgType::TITLE(), 'Достопримечательности ' . $row['pc_inwheretext']);
            $this->pageContent->getHead()->addOGMeta(
                OgType::DESCRIPTION(),
                $row['pc_description'] . ($row['pc_announcement'] ? '. ' . $row['pc_announcement'] : '')
            );
            $this->pageContent->getHead()->addOGMeta(OgType::UPDATED_TIME(), $this->lastedit_timestamp);
            if ($row['pc_coverphoto_id']) {
                $ph = new MPhotos($this->db);
                $photo = $ph->getItemByPk($row['pc_coverphoto_id']);
                $cityImage = Urls::getAbsoluteURL($photo['ph_src']);
                $this->pageContent->getHead()->addOGMeta(OgType::IMAGE(), $cityImage);
            } else {
                $cityImage = null;
            }

            $this->smarty->assign('city', $row);
            $this->smarty->assign('points', $points_data['points']);
            $this->smarty->assign('points_sight', $points_data['points_sight']);
            $this->smarty->assign('points_servo', $points_data['points_service']);
            $this->smarty->assign('page_url', _URL_ROOT);
            $this->smarty->assign('page_image', $cityImage);
            $this->smarty->assign('types_select', $points_data['types']);
            $this->smarty->assign('ptypes', []);
            $this->pageContent->setCustomJsModule('city');

            if ($this->webUser->isEditor()) {
                return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/pagecity.edit.tpl');
            } else {
                return $this->smarty->fetch(_DIR_TEMPLATES . '/_pages/pagecity.show.tpl');
            }
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @param string $url
     */
    private function showPageMap(string $url): void
    {
        $pc = new MPageCities($this->db);
        $city = $pc->getCityByUrl(str_replace('/map.html', '', $url));
        $this->pageHeaders->add("Location: /map/#center={$city['pc_longitude']},{$city['pc_latitude']}&zoom={$city['pc_latlon_zoom']}");
        $this->pageHeaders->flush();
        exit();
    }

    /**
     * @param MyDb $db
     * @param SiteRequest $request
     *
     * @return Core
     */
    public static function getInstance(MyDb $db, SiteRequest $request): Core
    {
        return self::getInstanceOf(__CLASS__, $db, $request);
    }
}
