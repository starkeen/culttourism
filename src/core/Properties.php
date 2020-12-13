<?php

declare(strict_types=1);

namespace app\core;

use RuntimeException;

/**
 * DTO с базовыми настройками
 * @property string $sitename
 * @property string $default_pagekeywords
 * @property string $default_pagedescription
 * @property string $default_pagetitle
 * @property string $title_delimiter
 * @property string $main_rss
 * @property string $stat_city
 * @property string $stat_points
 * @property string $mainfile_css
 * @property string $mainfile_js
 * @property string $key_yandexmaps
 * @property string $mail_fromaddr
 * @property string $mail_to
 * @property string $mail_feedback
 * @property string $stat_text
 * @property string $checklinks_shift
 * @property string $index_cnt_blogs
 * @property string $index_cnt_news
 * @property string $res_js_list
 * @property string $res_js_map
 * @property string $res_js_city
 * @property string $res_js_point
 * @property string $res_js_editor
 * @property string $site_active
 * @property string $app_openweather_key
 */
class Properties
{
    private const KEYS = [
        'sitename',
        'default_pagekeywords',
        'default_pagedescription',
        'default_pagetitle',
        'title_delimiter',
        'main_rss',
        'stat_city',
        'stat_points',
        'mainfile_css',
        'mainfile_js',
        'key_yandexmaps',
        'mail_fromaddr',
        'mail_to',
        'mail_feedback',
        'stat_text',
        'checklinks_shift',
        'index_cnt_blogs',
        'index_cnt_news',
        'res_js_list',
        'res_js_map',
        'res_js_city',
        'res_js_point',
        'res_js_editor',
        'site_active',
        'app_openweather_key',
    ];

    private $values = [];

    public function __isset($name): bool
    {
        return isset($this->values[$name]) && in_array($name, self::KEYS, true);
    }

    public function __set($name, $value): void
    {
        if (!in_array($name, self::KEYS, true)) {
            throw new RuntimeException('Указано несуществующее свойство');
        }

        $this->values[$name] = $value;
    }

    public function __get($name): string
    {
        if (isset($this->values[$name]) && in_array($name, self::KEYS, true)) {
            return $this->values[$name];
        }

        throw new RuntimeException('Запрошено несуществующее свойство');
    }
}
