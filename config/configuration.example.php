<?php

define('GLOBAL_ERROR_REPORTING', true);

define('GLOBAL_DIR_ROOT', '/www/vhosts/culttourism');
define('GLOBAL_URL_ROOT', 'cult.local');

// Sentry configuration
define('SENTRY_DSN', 'https://key1:key2@sentry.io/key3');
define('SENTRY_ORGANIZATION', 'org-name');
define('SENTRY_RELEASE_DSN', 'https://sentry.io/api/hooks/release/builtin/key4/key5/');

/* MySQL settings */
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'user');
define('DB_PASSWORD', 'pass');
define('DB_BASENAME', 'cult-base');
define('DB_PREFIX', 'cult');

/* Mail settings */
define('GLOBAL_MAIL_HOST', 'smtp.server-host.ru');
define('GLOBAL_MAIL_USER', 'noreply@culttourism.ru');
define('GLOBAL_MAIL_PASS', '-----');
define('GLOBAL_MAIL_FROMADDR', 'noreply@culttourism.ru');
define('GLOBAL_MAIL_FROMNAME', 'Культурный туризм');

define('YANDEX_XML_USER', 'user');
define('YANDEX_XML_KEY', 'absdef');

define('YANDEX_SEARCH_ID', 'abcdef'); // Folder ID
define('YANDEX_SEARCH_KEY', 'AABBccDDeeFF');

define('GOOGLE_CUSTOM_SEARCH_KEY', 'absdef');
define('GOOGLE_CUSTOM_SEARCH_CX', 'absdef');

// https://console.cloud.google.com/google/maps-apis/credentials?project=project-name
define('GOOGLE_STATIC_MAPS_API_KEY', 'AIza123456aBcDeF');

/*
 * Don't change anything below in this file!
 * You can edit HOSTING.CONF.PHP for change base url, base path
 * or MYSQL.CONF.PHP for change database settings
 */

/* Directory settings */
define('GLOBAL_DIR_DATA', GLOBAL_DIR_ROOT . '/data');
define('GLOBAL_DIR_PHOTOS', GLOBAL_DIR_DATA . '/photos');
define('GLOBAL_DIR_TEMPLATES', GLOBAL_DIR_ROOT . '/templates');
define('GLOBAL_DIR_VAR', GLOBAL_DIR_ROOT . '/var');
define('GLOBAL_DIR_CACHE', GLOBAL_DIR_VAR . '/cache');
define('GLOBAL_DIR_TMP', GLOBAL_DIR_VAR . '/tmp');

define('GLOBAL_SITE_URL', 'https://' . GLOBAL_URL_ROOT . '/');

