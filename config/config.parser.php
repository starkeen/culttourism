<?php

return [
    'komandirovka.ru' => [
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'tagsallow' => 'h1,div[class][id],p[class][id],a[href][class],a[class][href]',
        'list_items' => [
            "//div[@class='ajax_objects clearm']/div/div/div[@class='detail_h']/a[1]", //приоритетные
            "//div[@class='ajax_objects clearm']/div/a[1]", //топ
            "//div[@class='ajax_objects clearm']/div/div/a[1]", //обычные
        ],
        'item' => [
            'title' => [
                'path' => ["//h1"],
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ],
            'text' => [
                'path' => ["//div[@class='all-object-describe js_toggle_mobile']"],
                'type' => 1,
            ],
            'addr' => [
                'path' => [
                    "//div[@class='obj_in_itwr clearm'][4]/div",
                    "//div[@class='obj_in_itwr clearm'][5]/div",
                ],
                'type' => 1,
            ],
            'phone' => [
                'path' => ["//a[@class='tel-num']"],
                'type' => 1,
            ],
            'web' => [
                'path' => [
                    "//div[@class='www']/a",
                ],
                'type' => 1,
            ],
        ],
    ],
    'sobory.ru' => [
        'encoding' => 'utf-8',
        'doctype' => 'HTML 4.01 Transitional',
        'tagsallow' => 'h1,div[class][id],p[class][id],a[href][class],a[class][href],address',
        'list_items' => [
            '//div[@class="ob_line_level_left"]/div/div[@class="ob_name"]/a',
        ],
        'item' => [
            'title' => [
                'path' => ["//h1"],
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ],
            'text' => [
                'path' => [
                    "//div[@class='vkladki_content_right_colum']",
                ],
                'type' => 1,
            ],
            'addr' => [
                'path' => [
                    "//address",
                ],
                'type' => 1,
            ],
            'web' => [
                'path' => [
                    "//div[@class='standart_content']/p[2]/a[last()]",
                ],
                'type' => 2,
            ],
            'geo_latlon' => [
                'path' => [
                    "//div[@class='standart_content']/p[3]",
                ],
                'type' => 1,
            ],
        ],
    ],
    'autotravel.ru' => [
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'tagsallow' => 'h1,div[class][id],p[class][id],a[href][class],a[class][href],br,font[class]',
        'list_items' => [
            '//a[@class="travell5r"]',
        ],
        'item' => [
            'title' => [
                'path' => ["//h1"],
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ],
            'text' => [
                'path' => [
                    "//p[@class='t12 tl travell0t']/text()[1]",
                ],
                'type' => 1,
            ],
            'addr' => [
                'path' => [
                    "//div[@class='t12 tl travell0t']/text()[1]",
                ],
                'type' => 1,
            ],
            'phone' => [
                'path' => [
                    "//p[@class='t12 tl travell0t']/text()[4]",
                ],
                'type' => 1,
            ],
            'web' => [
                'path' => [
                    //
                ],
                'type' => 2,
            ],
            'worktime' => [
                'path' => [
                    "//p[@class='t12 tl travell0t']/text()[3]",
                ],
                'type' => 1,
            ],
            'geo_latlon_degmin' => [
                'path' => [
                    "//div[4]/div[2]",
                ],
                'type' => 1,
            ],
        ],
    ],
    'doroga.ua' => [
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'tagsallow' => 'h1,div[class][id],p[class][id],a[href][class],a[class][href],address',
        'list_items' => [
            '//a[@class="ObjectNameLink"]',
        ],
        'item' => [
            'title' => [
                'path' => ["//h1"],
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ],
            'text' => [
                'path' => [
                    "//div[@class='main-content']/div[7]/div[2]/div[5]/div[1]",
                    "//div[@class='main-content']/div[7]/div[2]/div[4]/div[2]",
                    "//div[@class='main-content']/div[7]/div[2]/div[4]/div[3]",
                    "//div[@class='main-content']/div[7]/div[2]/div[4]/div[4]",
                    "//div[@class='main-content']/div[7]/div[2]/div[4]/div[5]",
                ],
                'type' => 1,
            ],
            'geo_latlon_degminsec' => [
                'path' => [
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[3]/div[1]",
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[4]/div[1]",
                ],
                'type' => 1,
            ],
            'addr' => [
                'path' => [
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[4]",
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[5]",
                ],
                'type' => 1,
            ],
            'phone' => [
                'path' => [
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[6]",
                ],
                'type' => 1,
            ],
            'web' => [
                'path' => [
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/a[1]",
                ],
                'type' => 2,
            ],
            'worktime' => [
                'path' => [
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[7]",
                ],
                'type' => 1,
            ],
        ],
    ],
    'vetert.ru' => [
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'tagsallow' => 'h1,div[id],div[class],*[class],p,a[href]',
        'list_items' => [
            '//div[@class="evlist"]/p/a[1]',
            '//div[@class="evlist"]/p/a[2]',
        ],
        'item' => [
            'title' => [
                'path' => ["//h1"],
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ],
            'text' => [
                'path' => [
                    "//div[@id='content']",
                    "//div[1]/div[5]/div[3]/p",
                ],
                'delimiter' => "\n",
                'type' => 1,
            ],
            'geo_latlon_degmin1' => [
                'path' => [
                    "//div[1]/div[5]/div[3]/p[1]",
                ],
                'type' => 1,
            ],
        ],
    ],
    'rutraveller.ru' => [
        'encoding' => 'windows-1251',
        'doctype' => 'XHTML 1.0 Transitional',
        'tagsallow' => 'h1,div[id],div[class],span[class],*[class],p,a[href],ul[class],ul[id],li[class],li[id]',
        'list_items' => [
            '//a[@class="plc15-item-ttl"]',
        ],
        'item' => [
            'title' => [
                'path' => ["//h1"],
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ],
            'text' => [
                'path' => [
                    "//div[@class='place-section place-description']/div[@class='text-min']/div[@class='text'][1]",
                ],
                'delimiter' => "\n",
                'type' => 1,
            ],
            'addr' => [
                'path' => [
                    "//ul[@class='info-line info-line--regular']/li/a[1]",
                ],
                'type' => 1,
            ],
            'phone' => [
                'path' => [
                    "//div[@class='onmap-info']/div[1]/div[@class='onmap-info__item'][2]/div[2]",
                ],
                'type' => 1,
            ],
            'worktime' => [
                'path' => [
                    "//div[@class='onmap-info']/div[2]/div[@class='onmap-info__item'][1]/div[2]",
                ],
                'type' => 1,
            ],
            'geo_latlon_degmin1' => [
                'path' => [
                    "//div[1]/div[5]/div[3]/p[1]",
                ],
                'type' => 1,
            ],
        ],
    ],
];
