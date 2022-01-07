<?php

declare(strict_types=1);

namespace app\crontab;

use app\utils\Strings;
use MPageCities;
use MPhotos;

class MakePhotoFromMapCommand extends AbstractCrontabCommand
{
    private const API_KEY = 'AIzaSyBiuHllm_OCLEKww8y02DJPeePMtvEnTiE';

    private const DIR_ABSOLUTE = GLOBAL_DIR_DATA . '/photos/maps';
    private const DIR_RELATIVE = '/data/photos/maps';

    private const HEIGHT = 500;
    private const WIDTH = 500;

    private MPhotos $photosModel;
    private MPageCities $citiesModel;

    public function __construct(MPhotos $photos, MPageCities $cities)
    {
        $this->photosModel = $photos;
        $this->citiesModel = $cities;
    }

    public function run(): void
    {
        $cities = $this->citiesModel->getCityPagesWithoutPhotos();

        foreach ($cities as $pc) {
            $url = sprintf(
                "https://maps.googleapis.com/maps/api/staticmap?center=%F,%F&zoom=%d&size=%dx%d&maptype=roadmap&key=%s",
                $pc['pc_latitude'],
                $pc['pc_longitude'],
                $pc['pc_latlon_zoom'],
                self::WIDTH,
                self::HEIGHT,
                self::API_KEY
            );

            $cityMapName = str_replace(' ', '_', preg_replace("/[^a-zA-ZА-Яа-я0-9ё\s]/ui", '', mb_strtolower($pc['pc_title_unique'])));
            $fileName = sprintf(
                'map_%dx%d_%s.png',
                self::WIDTH,
                self::WIDTH,
                Strings::getTransliteration($cityMapName)
            );

            if (copy($url, self::DIR_ABSOLUTE . '/' . $fileName)) {
                $id = $this->photosModel->insert(
                    [
                        'ph_src' => self::DIR_RELATIVE . '/' . $fileName,
                        'ph_title' => $pc['pc_title_unique'],
                        'ph_author' => 'Google Maps',
                        'ph_link' => 'https://www.google.ru/maps/@' . $pc['pc_latitude'] . ',' . $pc['pc_longitude'] . ',' . $pc['pc_latlon_zoom'] . 'z?hl=ru',
                        'ph_width' => self::WIDTH,
                        'ph_height' => self::HEIGHT,
                        'ph_lat' => $pc['pc_latitude'],
                        'ph_lon' => $pc['pc_longitude'],
                        'ph_pc_id' => $pc['pc_id'],
                        'ph_date_add' => $this->photosModel->now(),
                        'ph_order' => 0,
                    ]
                );

                if ($id > 0) {
                    $this->citiesModel->updateByPk(
                        $pc['pc_id'],
                        [
                            'pc_coverphoto_id' => $id,
                        ]
                    );
                } else {
                    echo 'Нулевой идентификатор: ', $pc['pc_title_unique'];
                }
            } else {
                echo 'Ошибка загрузки карты: ', $pc['pc_title_unique'];
            }
        }
    }
}
