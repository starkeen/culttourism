<?php

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
include(realpath(dirname(__FILE__) . '/../config/configuration.php'));
include(_DIR_ROOT . '/includes/class.myDB.php');
include(_DIR_ROOT . '/includes/class.mySmarty.php');
include(_DIR_ROOT . '/includes/class.Logging.php');
include(_DIR_ROOT . '/includes/debug.php');

include(_DIR_INCLUDES . '/class.Helper.php');
spl_autoload_register('Helper::autoloader');

$db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);

$dbc = $db->getTableName('pagecity');

$json = '{"60199":{"id":60199,"level":2,"name":"Украина","feature":true,"index":25,"property":{"geoNamesId":690791,"iso3166":"UA"},"wikipedia":"ru:Украина"},"71022":{"id":71022,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Херсонская область","index":23,"property":{"geoNamesId":706442,"iso3166":"UA-65"},"wikipedia":"ru:Херсонская_область"},"71064":{"id":71064,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Волынская область","index":6,"property":{"geoNamesId":689064,"iso3166":"UA-07"},"wikipedia":"ru:Волынская_область"},"71236":{"id":71236,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Ровненская область","index":8,"property":{"geoNamesId":695592,"iso3166":"UA-56"},"wikipedia":"ru:Ровненская_область"},"71245":{"id":71245,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Житомирская область","index":9,"property":{"geoNamesId":686966,"iso3166":"UA-18"},"wikipedia":"ru:Житомирская_область"},"71248":{"id":71248,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Киевская область","index":20,"property":{"geoNamesId":703446,"iso3166":"UA-32"},"wikipedia":"ru:Киевская_область"},"71249":{"id":71249,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Черниговская область","index":14,"property":{"geoNamesId":710734,"iso3166":"UA-74"},"wikipedia":"ru:Черниговская_область"},"71250":{"id":71250,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Сумская область","index":13,"property":{"geoNamesId":692196,"iso3166":"UA-59"},"wikipedia":"ru:Сумская_область"},"71254":{"id":71254,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Харьковская область","index":18,"property":{"geoNamesId":706482,"iso3166":"UA-63"},"wikipedia":"ru:Харьковская_область"},"71971":{"id":71971,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Луганская область","index":10,"property":{"geoNamesId":702657,"iso3166":"UA-09"},"wikipedia":"ru:Луганская_область"},"71973":{"id":71973,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Донецкая область","index":16,"property":{"geoNamesId":709716,"iso3166":"UA-14"},"wikipedia":"ru:Донецкая_область"},"71980":{"id":71980,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Запорожская область","index":17,"property":{"geoNamesId":687699,"iso3166":"UA-23"},"wikipedia":"ru:Запорожская_область"},"72380":{"id":72380,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Львовская область","index":11,"property":{"geoNamesId":702549,"iso3166":"UA-46"},"wikipedia":"ru:Львовская_область"},"72488":{"id":72488,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Ивано-Франковская область","index":5,"property":{"geoNamesId":707470,"iso3166":"UA-26"},"wikipedia":"ru:Ивано-Франковская_область"},"72489":{"id":72489,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Закарпатская область","index":3,"property":{"geoNamesId":687869,"iso3166":"UA-21"},"wikipedia":"ru:Закарпатская_область"},"72525":{"id":72525,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Тернопольская область","index":1,"property":{"geoNamesId":691649,"iso3166":"UA-61"},"wikipedia":"ru:Тернопольская_область"},"72526":{"id":72526,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Черновицкая область","index":2,"property":{"geoNamesId":710720,"iso3166":"UA-77"},"wikipedia":"ru:Черновицкая_область"},"72634":{"id":72634,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Одесская область","index":24,"property":{"geoNamesId":698738,"iso3166":"UA-51"},"wikipedia":"ru:Одесская_область"},"72635":{"id":72635,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Николаевская область","index":12,"property":{"geoNamesId":700567,"iso3166":"UA-48"},"wikipedia":"ru:Николаевская_область"},"90726":{"id":90726,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Винницкая область","index":7,"property":{"geoNamesId":689559,"iso3166":"UA-05"},"wikipedia":"ru:Винницкая_область"},"90742":{"id":90742,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Хмельницкая область","index":4,"property":{"geoNamesId":706370,"iso3166":"UA-68"},"wikipedia":"ru:Хмельницкая_область"},"91278":{"id":91278,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Черкасская область","index":15,"property":{"geoNamesId":710802,"iso3166":"UA-71"},"wikipedia":"ru:Черкасская_область"},"91294":{"id":91294,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Полтавская область","index":19,"property":{"geoNamesId":696634,"iso3166":"UA-53"},"wikipedia":"ru:Полтавская_область"},"101746":{"id":101746,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Днепропетровская область","index":22,"property":{"geoNamesId":709929,"iso3166":"UA-12"},"wikipedia":"ru:Днепропетровская_область"},"101859":{"id":101859,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Кировоградская область","index":21,"property":{"geoNamesId":705811,"iso3166":"UA-35"},"wikipedia":"ru:Кировоградская_область"},"421866":{"id":421866,"level":4,"parents":[{"id":60199,"delta":-2}],"name":"Киев","index":0,"property":{"geoNamesId":703447,"iso3166":"UA-30"},"wikipedia":"ru:Киев"}}';

$data = (array) json_decode($json);

foreach ($data as $item) {
    $db->sql = "UPDATE $dbc
                SET pc_osm_id = '{$item->id}'
                WHERE pc_osm_id = 0 AND pc_title = '{$item->name}'
                LIMIT 1";
    //$db->showSQL();
    $db->exec();
}
