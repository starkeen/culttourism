<?php

return array(
    'komandirovka.ru' => array(
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'encoding' => 'utf-8',
        'tagsallow' => 'h1,div[class][id],p[class][id],a[href][class],a[class][href],address',
        'list_items' => array(
            "//div[@class='ajax_objects']/div/div/div[@class='detail_h']/a[1]", //приоритетные
            "//div[@class='ajax_objects']/div/a[1]", //топ
            "//div[@class='ajax_objects']/div/div/a[1]", //обычные
        ),
        'item' => array(
            'title' => array(
                'path' => array("//h1"),
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ),
            'text' => array(
                'path' => array("//div[@class='all-object-describe js_toggle_mobile']"),
                'type' => 1,
            ),
            'addr' => array(
                'path' => array(
                    "//div[@class='obj_in_itwr clearm'][4]/div",
                ),
                'type' => 1,
            ),
            'phone' => array(
                'path' => array("//a[@class='tel-num']"),
                'type' => 1,
            ),
            'web' => array(
                'path' => array(
                    "//div[@class='www']/a",
                ),
                'type' => 2,
            ),
        ),
    ),
    'sobory.ru' => array(
        'encoding' => 'windows-1251',
        'doctype' => 'HTML 4.01 Transitional',
        'tagsallow' => 'h1,div[class][id],p[class][id],a[href][class],a[class][href],address',
        'list_items' => array(
            '//div[@class="ob_line_level_left"]/div/div[@class="ob_name"]/a',
        ),
        'item' => array(
            'title' => array(
                'path' => array("//h1"),
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ),
            'text' => array(
                'path' => array(
                    "//div[@class='vkladki_content_right_colum']",
                ),
                'type' => 1,
            ),
            'addr' => array(
                'path' => array(
                    "//address",
                ),
                'type' => 1,
            ),
            'web' => array(
                'path' => array(
                    "//div[@class='standart_content']/p[2]/a[2]",
                ),
                'type' => 2,
            ),
        ),
    ),
    'autotravel.ru' => array(
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'tagsallow' => 'h1,div[class][id],p[class][id],a[href][class],a[class][href],address',
        'list_items' => array(
            '//a[@class="travell5n"]',
        ),
        'item' => array(
            'title' => array(
                'path' => array("//h1"),
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ),
            'text' => array(
                'path' => array(
                    "//p[@class='travell0u']",
                ),
                'type' => 1,
            ),
            'addr' => array(
                'path' => array(
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[3]",
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[4]",
                ),
                'type' => 1,
            ),
            'phone' => array(
                'path' => array(
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[4]",
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[5]",
                ),
                'type' => 1,
            ),
            'web' => array(
                'path' => array(
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/a[1]",
                ),
                'type' => 2,
            ),
            'worktime' => array(
                'path' => array(
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[6]",
                ),
                'type' => 1,
            ),
        ),
    ),
    'doroga.ua' => array(
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'tagsallow' => 'h1,div[class][id],p[class][id],a[href][class],a[class][href],address',
        'list_items' => array(
            '//a[@class="ObjectNameLink"]',
        ),
        'item' => array(
            'title' => array(
                'path' => array("//h1"),
                'type' => 1, //1-nodeValue; 2-getAttribute('href')
            ),
            'text' => array(
                'path' => array(
                    "//div[@class='main-content']/div[7]/div[2]/div[4]/div[2]",
                    "//div[@class='main-content']/div[7]/div[2]/div[4]/div[3]",
                    "//div[@class='main-content']/div[7]/div[2]/div[4]/div[4]",
                    "//div[@class='main-content']/div[7]/div[2]/div[4]/div[5]",
                ),
                'type' => 1,
            ),
            'addr' => array(
                'path' => array(
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[3]",
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[4]",
                ),
                'type' => 1,
            ),
            'phone' => array(
                'path' => array(
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[4]",
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[5]",
                ),
                'type' => 1,
            ),
            'web' => array(
                'path' => array(
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/a[1]",
                ),
                'type' => 2,
            ),
            'worktime' => array(
                'path' => array(
                    "//div[@class='main-content']/div[7]/div[2]/div[1]/div[6]",
                ),
                'type' => 1,
            ),
        ),
    ),
);
