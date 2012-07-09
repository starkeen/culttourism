<?php
define('_AUTH_EXPIRE_HOURS', 1); //auth expiration period after unuse any services in hours
define('_INDEXPAGE_URI', 'index.html');
define('_ER_REPORT', false);
define('_CACHE_DAYS', 0); //days for expire documents, 0 is no-cache
/*
 * Don't change anything below in this file!
 * You can edit HOSTING.CONF.PHP for change base url, base path
 * or MYSQL.CONF.PHP for change database settings
 */
include('hosting.conf.php');
include('mysql.conf.php');
include('path.conf.php');
include('mail.conf.php');

define('_SITE_URL', 'http://' . _URL_ROOT . '/');

ini_set('include_path', ini_get('include_path') . _INI_DELIMITER . _DIR_INCLUDES);


?>