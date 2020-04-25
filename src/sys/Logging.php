<?php

declare(strict_types=1);

namespace app\sys;

use app\db\FactoryDB;
use Core;
use MLogActions;
use MLogErrors;

class Logging
{
    /**
     * @param int $type
     *
     * @return bool
     */
    public static function writeError(int $type): bool
    {
        $db = FactoryDB::db();
        $le = new MLogErrors($db);
        if ((int) $type !== Core::HTTP_CODE_301 && !strpos($_SERVER['REQUEST_URI'], 'precomposed')) {
            $le->insert(
                [
                    'le_type' => (string) $type,
                    'le_date' => $le->now(),
                    'le_url' => $_SERVER['REQUEST_URI'],
                    'le_ip' => $_SERVER['REMOTE_ADDR'],
                    'le_browser' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'le_script' => $_SERVER['SCRIPT_FILENAME'],
                    'le_referer' => $_SERVER['HTTP_REFERER'] ?? 'undefined',
                ]
            );
        }
        return true;
    }

    /**
     * @param string $module
     * @param string $action
     * @param array $data
     *
     * @return bool
     */
    public static function addHistory(string $module, string $action, array $data = []): bool
    {
        $db = FactoryDB::db();
        $la = new MLogActions($db);
        $la->insert(
            [
                'la_date' => $la->now(),
                'la_module' => $module,
                'la_action' => $action,
                'la_text' => serialize($data),
            ]
        );
        return true;
    }

    /**
     * @param string $module_id
     * @param $time
     * @param string $url
     *
     * @return bool
     */
    public static function addDebug(string $module_id, $time, string $url = ''): bool
    {
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
