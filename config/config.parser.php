<?php

return array(
    'komandirovka.ru' => array(
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
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
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'list_items' => array(),
        'item' => array(),
    ),
    'autotravel.ru' => array(
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'list_items' => array(),
        'item' => array(),
    ),
    'doroga.ua' => array(
        'encoding' => 'utf-8',
        'doctype' => 'XHTML 1.0 Transitional',
        'list_items' => array(),
        'item' => array(),
    ),
);
