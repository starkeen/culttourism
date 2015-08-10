<?php

/**
 * Description of class MyDB
 *
 * @author Andrey_Pns
 */
include 'class.MyMysql.php';

class MyDB extends MyMysql {

    public function getTableName($alias) {
        if ($this->prefix === null) {
            return '`' . $alias . '`';
        } else {
            return '`' . $this->prefix . '_' . $alias . '`';
        }
    }

}
