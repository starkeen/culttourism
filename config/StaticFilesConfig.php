<?php

declare(strict_types=1);

namespace config;

use app\core\assets\constant\Pack;
use app\core\assets\StaticFilesConfigInterface;

class StaticFilesConfig implements StaticFilesConfigInterface
{
    private const JS_COMMON = [
        GLOBAL_DIR_ROOT . '/addons/jquery/jquery.2.1.3.min.js',
        GLOBAL_DIR_ROOT . '/addons/jquery/jquery-migrate-1.2.1.min.js',
        GLOBAL_DIR_ROOT . '/addons/simplemodal/jquery.simplemodal.1.4.4.min.js',
        GLOBAL_DIR_ROOT . '/addons/autocomplete/jquery.autocomplete.min.js',
        GLOBAL_DIR_ROOT . '/js/main.js',
    ];

    /**
     * @inheritDoc
     */
    public function getCSSList(): array
    {
        return [
            Pack::COMMON => [
                GLOBAL_DIR_ROOT . '/css/common-layout.css',
                GLOBAL_DIR_ROOT . '/addons/autocomplete/autocomplete.css',
                GLOBAL_DIR_ROOT . '/addons/simplemodal/simplemodal.css',
                GLOBAL_DIR_ROOT . '/css/common-modules.css',
                GLOBAL_DIR_ROOT . '/css/common-print.css',
                GLOBAL_DIR_ROOT . '/css/common-media-queries.css',
            ],
            Pack::API => [
                GLOBAL_DIR_ROOT . '/css/api.css',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getJavascriptList(): array
    {
        return [
            Pack::COMMON => self::JS_COMMON,
            Pack::MAP => array_merge(self::JS_COMMON, [
                GLOBAL_DIR_ROOT . '/js/map.js',
            ]),
            Pack::LIST => array_merge(self::JS_COMMON, [
                GLOBAL_DIR_ROOT . '/js/map_page_list.js',
            ]),
            Pack::CITY => array_merge(self::JS_COMMON, [
                GLOBAL_DIR_ROOT . '/js/map_page_city.js',
                GLOBAL_DIR_ROOT . '/js/adv_city.js',
            ]),
            Pack::POINT => array_merge(self::JS_COMMON, [
                GLOBAL_DIR_ROOT . '/js/adv_point.js',
                GLOBAL_DIR_ROOT . '/js/map_page_point.js',
            ]),
            Pack::API => [
                GLOBAL_DIR_ROOT . '/js/api.js',
            ],
            Pack::EDITOR => [
                GLOBAL_DIR_ROOT . '/addons/jquery.ui/jquery.ui.core.js',
                GLOBAL_DIR_ROOT . '/addons/jquery.ui/jquery.ui.datepicker.js',
                GLOBAL_DIR_ROOT . '/addons/jquery.ui/jquery.ui.datepicker-ru.js',
            ],
        ];
    }
}
