<?php
define('_AUTH_EXPIRE_HOURS', 1); //auth expiration period after unuse any services in hours
define('_INDEXPAGE_URI', 'index.html');
define('_ER_REPORT', false);
define('_CACHE_DAYS', 0); //days for expire documents, 0 is no-cache

//define('_DIR_ROOT', $_SERVER['DOCUMENT_ROOT'] . 'cult');
define('_DIR_ROOT', 'd:\webserver\htdocs\cult');
define('_INI_DELIMITER', ';');                  // use ';' for Windows and ':' for Unix
define('_DELIMITER_PATH', '\\');
define('_URL_ROOT', 'cult.localhost');
/* MySQL settings */
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_BASENAME', 'cult');
define('DB_PREFIX', 'cult');
/* Mail settings */
define('_MAIL_HOST', '--');
define('_MAIL_USER', '----');
define('_MAIL_PASS', '----');
define('_MAIL_FROMADDR', '---');
define('_MAIL_FROMNAME', 'Культурный туризм');
define('_MAIL_TO', '---');
define('_FEEDBACK_MAIL', '----');

/*
 * Don't change anything below in this file!
 * 
 */
/* Directory settings */
define('_DIR_INCLUDES', _DIR_ROOT . _DELIMITER_PATH . 'includes');
define('_DIR_TEMPLATES', _DIR_ROOT . _DELIMITER_PATH . 'templates');
define('_DIR_TEMPLATES_ADM', _DIR_ROOT . _DELIMITER_PATH . 'templates' . _DELIMITER_PATH . '_admin');
define('_DIR_MODULES', _DIR_ROOT . _DELIMITER_PATH . 'modules');
define('_DIR_MODELS', _DIR_ROOT . _DELIMITER_PATH . 'models');
define('_DIR_ADDONS', _DIR_ROOT . _DELIMITER_PATH . 'addons');
define('_DIR_SMARTY', _DIR_ROOT . _DELIMITER_PATH . 'addons/Smarty3/libs');
include('mail.conf.php');

define('_SITE_URL', 'http://' . _URL_ROOT . '/');

ini_set('include_path', ini_get('include_path') . _INI_DELIMITER . _DIR_INCLUDES);


?>
