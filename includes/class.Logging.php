<?php

class Logging {

    public static function write($type, $text = null) {
        global $db;
        $url = $_SERVER['REQUEST_URI'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $script = $_SERVER['SCRIPT_FILENAME'];
        $browser = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : 'undefined';
        if ($type != '301' && !strpos($url, 'precomposed')) {
            $dbe = $db->getTableName('log_errors');
            $db->sql = "INSERT INTO $dbe
                    (le_type, le_date, le_url, le_ip, le_browser, le_script, le_referer)
                    VALUES
                    ('$type' , now(), '$url', '$ip', '$browser', '$script', '$referer')";
            $db->exec();
        }
        return true;
    }

    public static function addHistory($module, $action, $data = array()) {
        global $db;
        $dba = $db->getTableName('log_actions');
        $text = $db->getEscapedString(serialize($data));
        $module = $db->getEscapedString($module);
        $action = $db->getEscapedString($action);
        $db->sql = "INSERT INTO $dba
                    (la_date, la_module, la_action, la_text)
                    VALUES
                    (now(), '$module', '$action', '$text')";
        $db->exec();
        return true;
    }

    public static function addDebug($module_id, $time, $url = '') {
        global $db;
        $dbld = $db->getTableName('log_debug');
        $url = $db->getEscapedString($url);
        $module = $db->getEscapedString($module_id);
        $time = $db->getEscapedString($time);
        $db->sql = "INSERT INTO $dbld SET
                    ld_date = now(),
                    ld_module = '$module',
                    ld_url = '$url',
                    ld_worktime = '$time'";
        $db->exec();
        return true;
    }

}

?>