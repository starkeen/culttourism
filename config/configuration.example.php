<?php

define('_AUTH_EXPIRE_HOURS', 1); //auth expiration period after unuse any services in hours
define('_INDEXPAGE_URI', 'index.html');
define('_ER_REPORT', true);
define('_CACHE_DAYS', 0); //days for expire documents, 0 is no-cache

define('_DIR_ROOT', '/www/vhosts/culttourism');
define('_INI_DELIMITER', ':');                  // use ';' for Windows and ':' for Unix
define('_DELIMITER_PATH', '/');
define('_URL_ROOT', 'cult.local');

// Sentry configuration
define('SENTRY_DSN', 'https://key1:key2@sentry.io/key3');
define('SENTRY_RELEASE_DSN', 'https://sentry.io/api/hooks/release/builtin/key4/key5/');

/* MySQL settings */
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'user');
define('DB_PASSWORD', 'pass');
define('DB_BASENAME', 'cult-base');
define('DB_PREFIX', 'cult');

/* Mail settings */
define('_MAIL_HOST', 'smtp.server-host.ru');
define('_MAIL_USER', 'noreply@culttourism.ru');
define('_MAIL_PASS', '-----');
define('_MAIL_FROMADDR', 'noreply@culttourism.ru');
define('_MAIL_FROMNAME', 'Культурный туризм');
define('_MAIL_TO', 'xxxxx@gmail.com');
define('_FEEDBACK_MAIL', 'xxx@mail.ru');

define('YANDEX_XML_USER', 'user');
define('YANDEX_XML_KEY', 'absdef');

/*
 * Don't change anything below in this file!
 * You can edit HOSTING.CONF.PHP for change base url, base path
 * or MYSQL.CONF.PHP for change database settings
 */

/* Directory settings */
define('_DIR_INCLUDES', _DIR_ROOT . _DELIMITER_PATH . 'includes');
define('_DIR_DATA', _DIR_ROOT . _DELIMITER_PATH . 'data');
define('_DIR_TEMPLATES', _DIR_ROOT . _DELIMITER_PATH . 'templates');
define('_DIR_TEMPLATES_ADM', _DIR_ROOT . _DELIMITER_PATH . 'templates' . _DELIMITER_PATH . '_admin');
define('_DIR_MODULES', _DIR_ROOT . _DELIMITER_PATH . 'modules');
define('_DIR_MODELS', _DIR_ROOT . _DELIMITER_PATH . 'models');
define('_DIR_ADDONS', _DIR_ROOT . _DELIMITER_PATH . 'addons');
define('_DIR_SMARTY', _DIR_ROOT . _DELIMITER_PATH . 'addons/Smarty3/libs');

define('_SITE_URL', 'https://' . _URL_ROOT . '/');

ini_set('include_path', ini_get('include_path') . _INI_DELIMITER . _DIR_INCLUDES);
