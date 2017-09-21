<?php

class FactoryDB {

    protected static $db;

    public static function db() {
        if (is_null(self::$db)) {
            self::$db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);
        }
        return self::$db;
    }

}
