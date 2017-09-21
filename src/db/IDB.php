<?php

namespace app\db;

interface IDB
{
    public function __construct($db_host, $db_user, $db_pwd, $db_base, $db_prefix = null);

    public function getTableName($alias);

    public function getEscapedString($text);

    public function exec($sql = null);

    public function fetch($res = null);

    public function fetchCol($res = null);

    public function fetchAll($res = null);

    public function getLastInserted();

    public function getAffectedRows();

    public function showSQL($sql = null);

    public function getDebugInfo();

    public function getDebugLast();

    public function getDebugInfoText();
}
