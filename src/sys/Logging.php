<?php

declare(strict_types=1);

namespace app\sys;

use app\db\FactoryDB;
use MLogActions;

class Logging
{
    /**
     * @param string $module
     * @param string $action
     * @param array  $data
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
