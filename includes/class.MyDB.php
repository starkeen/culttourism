<?php

/**
 * Description of class MyDB
 *
 * @author Andrey_Pns
 */
include 'class.MyPDO.php';

class MyDB extends MyPDO {

    public function getTableName($alias) {
        if ($this->prefix === null) {
            return '`' . $alias . '`';
        } else {
            return '`' . $this->prefix . '_' . $alias . '`';
        }
    }

    public function exec($sql = null) {
        $this->log();
        parent::exec($sql);
    }

    public function log() {
        $query = $this->sql;
        $query_mask = preg_replace('/\d/', '', $query);
        if (false && !strstr($query_mask, 'cult_authorizations')) {
            $filename_log = _DIR_DATA . '/logs/sql_log_' . md5($query_mask) . '.log';
            $filename_sql = _DIR_DATA . '/logs/sql_sql_' . md5($query_mask) . '.log';
            if (!file_exists($filename_sql)) {
                file_put_contents($filename_sql, $query . PHP_EOL);
            }
            file_put_contents($filename_log, date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
        }
    }

}
