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
        if (true) {
            $filename_log = _DIR_DATA . '/logs/sql_' . md5($query_mask) . '.log';
            if (!file_exists($filename_log)) {
                file_put_contents($filename_log, $query . PHP_EOL);
                file_put_contents($filename_log, '============' . PHP_EOL, FILE_APPEND);
                file_put_contents($filename_log, str_pad('', 1000-mb_strlen($query), '#'). PHP_EOL, FILE_APPEND);
                file_put_contents($filename_log, '------------' . PHP_EOL, FILE_APPEND);
            }
            file_put_contents($filename_log, date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
        }
    }

}
