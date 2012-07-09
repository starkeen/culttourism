<?php
class Logging {
    public static function write($type, $text=null) {
        global $db;
        if ($text) $type .= ' #:' . $text;
        $url = $_SERVER['REQUEST_URI'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $script = $_SERVER['SCRIPT_FILENAME'];
        $browser = $_SERVER['HTTP_USER_AGENT'];
        $referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : 'undefined';
        $dbe = $db->getTableName('log_errors');
        $db->sql = "INSERT INTO $dbe
                    (le_type, le_date, le_url, le_ip, le_browser, le_script, le_referer)
                    VALUES
                    ('$type' , now(), '$url', '$ip', '$browser', '$script', '$referer')";
        $db->exec();
    }
}
?>