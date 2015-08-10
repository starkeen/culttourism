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

}
