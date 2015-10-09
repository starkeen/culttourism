<?php

class Logging {

    public static function write($type, $text = null) {
        $db = FactoryDB::db();
        $le = new MLogErrors($db);
        if ($type != '301' && !strpos($_SERVER['REQUEST_URI'], 'precomposed')) {
            $le->insert(array(
                'le_type' => $type,
                'le_date' => $le->now(),
                'le_url' => $_SERVER['REQUEST_URI'],
                'le_ip' => $_SERVER['REMOTE_ADDR'],
                'le_browser' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
                'le_script' => $_SERVER['SCRIPT_FILENAME'],
                'le_referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'undefined',
            ));
        }
        return true;
    }

    public static function addHistory($module, $action, $data = array()) {
        $db = FactoryDB::db();
        $la = new MLogActions($db);
        $la->insert(array(
            'la_date' => $la->now(),
            'la_module' => $module,
            'la_action' => $action,
            'la_text' => serialize($data),
        ));
        return true;
    }

    public static function addDebug($module_id, $time, $url = '') {
        $db = FactoryDB::db();
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
