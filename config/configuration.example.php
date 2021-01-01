<?php

define('_ER_REPORT', true);

define('_DIR_ROOT', '/www/vhosts/culttourism');
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
define('GOOGLE_CUSTOM_SEARCH_KEY', 'absdef');
define('GOOGLE_CUSTOM_SEARCH_CX', 'absdef');

/*
 * Don't change anything below in this file!
 * You can edit HOSTING.CONF.PHP for change base url, base path
 * or MYSQL.CONF.PHP for change database settings
 */

/* Directory settings */
define('_DIR_DATA', _DIR_ROOT . '/data');
define('_DIR_PHOTOS', _DIR_DATA . '/photos');
define('_DIR_TEMPLATES', _DIR_ROOT . '/templates');
define('_DIR_VAR', _DIR_ROOT . '/var');
define('_DIR_CACHE', _DIR_VAR . '/cache');
define('_DIR_TMP', _DIR_VAR . '/tmp');

define('_SITE_URL', 'https://' . _URL_ROOT . '/');

