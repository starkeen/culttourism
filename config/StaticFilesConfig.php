<?php

declare(strict_types=1);

namespace config;

use app\core\assets\constant\Pack;
use app\core\assets\constant\Type;
use app\core\assets\StaticFilesConfigInterface;
use InvalidArgumentException;

class StaticFilesConfig implements StaticFilesConfigInterface
{
    private const JS_COMMON = [
        _DIR_ROOT . '/addons/jquery/jquery.2.1.3.min.js',
        _DIR_ROOT . '/addons/jquery/jquery-migrate-1.2.1.min.js',
        _DIR_ROOT . '/addons/simplemodal/jquery.simplemodal.1.4.4.min.js',
        _DIR_ROOT . '/addons/autocomplete/jquery.autocomplete.min.js',
    ];

    public function getFiles(Type $type, Pack $pack): array
    {
        $all = null;
        switch ($type->getValue()) {
            case Type::CSS:
                $all = $this->getCSSList();
                break;
            case Type::JS:
                $all = $this->getJavascriptList();
                break;
            default:
                throw new InvalidArgumentException('Неизвестный тип');
        }

        return $all[$pack->getValue()];
    }

    /**
     * @inheritDoc
     */
    public function getCSSList(): array
    {
        return [
            Pack::COMMON => [
                _DIR_ROOT . '/css/common-layout.css',
                _DIR_ROOT . '/addons/autocomplete/autocomplete.css',
                _DIR_ROOT . '/addons/simplemodal/simplemodal.css',
                _DIR_ROOT . '/css/common-modules.css',
                _DIR_ROOT . '/css/common-print.css',
                _DIR_ROOT . '/css/common-media-queries.css',
            ],
            Pack::API => [
                _DIR_ROOT . '/css/api.css',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getJavascriptList(): array
    {
        return [
            Pack::COMMON => array_merge(self::JS_COMMON, [
                _DIR_ROOT . '/js/main.js',
            ]),
            Pack::MAP => array_merge(self::JS_COMMON, [
                _DIR_ROOT . '/js/main.js',
                _DIR_ROOT . '/js/map.js',
            ]),
            Pack::LIST => array_merge(self::JS_COMMON, [
                _DIR_ROOT . '/js/main.js',
                _DIR_ROOT . '/js/map_page_list.js',
            ]),
            Pack::CITY => array_merge(self::JS_COMMON, [
                _DIR_ROOT . '/js/main.js',
                _DIR_ROOT . '/js/map_page_city.js',
                _DIR_ROOT . '/js/adv_city.js',
            ]),
            Pack::POINT => array_merge(self::JS_COMMON, [
                _DIR_ROOT . '/js/main.js',
                _DIR_ROOT . '/js/adv_point.js',
                _DIR_ROOT . '/js/map_page_point.js',
            ]),
            Pack::API => [
                _DIR_ROOT . '/js/api.js',
            ],
            Pack::EDITOR => [
                _DIR_ROOT . '/addons/jquery.ui/jquery.ui.core.js',
                _DIR_ROOT . '/addons/jquery.ui/jquery.ui.datepicker.js',
                _DIR_ROOT . '/addons/jquery.ui/jquery.ui.datepicker-ru.js',
            ],
        ];
    }
}
