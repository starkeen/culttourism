<?php

const GLOBAL_ERROR_REPORTING = true;

const GLOBAL_DIR_ROOT = '/www/vhosts/culttourism';
const GLOBAL_URL_ROOT = 'cult.local';

/* MySQL settings */
const DB_HOSTNAME = 'localhost';
const DB_USERNAME = 'user';
const DB_PASSWORD = 'pass';
const DB_BASENAME = 'cult-base';
const DB_PREFIX = 'cult';

/* Mail settings */
const GLOBAL_MAIL_HOST = 'smtp.server-host.ru';
const GLOBAL_MAIL_USER = 'noreply@culttourism.ru';
const GLOBAL_MAIL_PASS = '-----';
const GLOBAL_MAIL_FROMADDR = 'noreply@culttourism.ru';
const GLOBAL_MAIL_FROMNAME = 'Культурный туризм';

// https://username.sentry.io/settings/projects/project-name/keys/
const SENTRY_DSN = 'https://key1:key2@sentry.io/key3';
const SENTRY_ORGANIZATION = 'org-name';

// https://console.cloud.yandex.ru/folders/
const YANDEX_SEARCH_ID = 'abcdef'; // Folder ID
const YANDEX_SEARCH_KEY = 'AABBccDDeeFF';

// https://oauth.yandex.com/client/abcdef
const YANDEX_WEBMASTER_USER_ID = 10123456;
const YANDEX_WEBMASTER_TOKEN = 'AQAAAAAArABCDEFHGHIJKLMNOPQRSTUVWXYZ';

// https://console.cloud.google.com/google/maps-apis/credentials?project=project-name
const GOOGLE_CUSTOM_SEARCH_KEY = 'absdef';
const GOOGLE_CUSTOM_SEARCH_CX = 'absdef';

// https://console.cloud.google.com/google/maps-apis/credentials?project=project-name
const GOOGLE_STATIC_MAPS_API_KEY = 'AIza123456aBcDeF';

// https://www.google.com/recaptcha/admin/
const RECAPTCHA_KEY = 'abcdefghijk';
const RECAPTCHA_SECRET = 'ABCDEFGHIJK';

/*
 * ============================================================
 * Don't change anything below in this file!
 * You can edit HOSTING.CONF.PHP for change base url, base path
 * or MYSQL.CONF.PHP for change database settings
 * ============================================================
 */

/* Directory settings */
const GLOBAL_DIR_DATA = GLOBAL_DIR_ROOT . '/data';
const GLOBAL_DIR_PHOTOS = GLOBAL_DIR_DATA . '/photos';
const GLOBAL_DIR_TEMPLATES = GLOBAL_DIR_ROOT . '/templates';
const GLOBAL_DIR_VAR = GLOBAL_DIR_ROOT . '/var';
const GLOBAL_DIR_CACHE = GLOBAL_DIR_VAR . '/cache';
const GLOBAL_DIR_TMP = GLOBAL_DIR_VAR . '/tmp';

const GLOBAL_SITE_URL = 'https://' . GLOBAL_URL_ROOT . '/';
