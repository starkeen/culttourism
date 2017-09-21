<?php

/**
 * Description of MyMysql
 *
 * @author Andrey_Pns
 */
include 'interfaces/IDB.php';

class MyMysql implements IDB {

    static $instances = 0;
    public $link = null;
    protected $prefix = null;
    public $sql = '';
    private $res = null;
    private $last_inserted_id = null;
    private $affected_rows = null;
    private $timer_start = 0;
    private $cnt_queries = 0; //счетчик выполненных запросов
    private $cnt_worktime_this = 0; //счетчик времени работы текущего запроса в секундах
    private $cnt_worktime_all = 0; //счетчик времени работы всех запросов в секундах

    public function __construct($db_host, $db_user, $db_pwd, $db_base, $db_prefix = null) {
        if (MyDB::$instances === 0) {
            $this->link = @mysql_connect($db_host, $db_user, $db_pwd);
            if ($this->link) {
                if (!@mysql_select_db($db_base, $this->link)) {
                    $this->link = null;
                    return $this;
                }
                @mysql_query("/*!40101 SET NAMES 'utf8' */");
                $this->prefix = $db_prefix;
                MyDB::$instances = 1;
            } else {
                return $this;
            }
            //$this->timer_start = microtime(true);
        } else {
            return $this;
        }
    }

    public function getTableName($alias) {
        if ($this->prefix === null) {
            return '`' . $alias . '`';
        } else {
            return '`' . $this->prefix . '_' . $alias . '`';
        }
    }

    public function exec($sql = null) {
        $this->timer_start = microtime(true);
        if ($sql !== null) {
            $this->sql = $sql;
        }
        if (_ER_REPORT) {
            $this->res = mysql_query($this->sql) or die(mysql_error() . $this->sql);
        } else {
            $this->res = mysql_query($this->sql);
        }
        $this->last_inserted_id = @mysql_insert_id($this->link);
        $this->affected_rows = @mysql_affected_rows($this->link);
        $this->cnt_queries++;
        $this->cnt_worktime_this = microtime(true) - $this->timer_start;
        $this->cnt_worktime_all += $this->cnt_worktime_this;

        if (true == false) {
            $debug_log_text = "=================================\n";
            $debug_log_text .= date('d.m.Y H:i:s') . "\n";
            $debug_log_text .= $this->sql . "\n";
            $debug_log_text .= "--------------------\n";
            $debug_log_text .= "$this->cnt_worktime_this\n";
            $debug_log_text .= "=================================\n";
            $path_to_save = _DIR_ROOT . '/tmp' . $_SERVER['REQUEST_URI'];
            if (!file_exists($path_to_save)) {
                mkdir($path_to_save);
            }
            file_put_contents($path_to_save . session_id() . '.txt', $debug_log_text, FILE_APPEND);
        }

        return $this->res;
    }

    public function fetch($res = null) {
        if ($res) {
            $this->res = $res;
        }
        if (is_resource($this->res)) {
            return mysql_fetch_assoc($this->res);
        }
    }

    public function fetchCol($res = null) {
        if ($res) {
            $this->res = $res;
        }
        if (is_resource($this->res)) {
            $row = mysql_fetch_assoc($this->res);
            return array_shift($row);
        }
    }

    public function fetchAll($res = null) {
        if ($res) {
            $this->res = $res;
        }
        $out = array();
        if (is_resource($this->res)) {
            while ($row = $this->fetch()) {
                $out[] = $row;
            }
        }
        return $out;
    }

    public function getLastInserted() {
        return $this->last_inserted_id;
    }

    public function getAffectedRows() {
        return $this->affected_rows;
    }

    public function getEscapedString($text) {
        return mysql_real_escape_string($text, $this->link);
    }

    public function beginTransaction() {
        $this->sql = 'START TRANSACTION';
        $this->exec();
    }

    public function commitTransaction() {
        $this->sql = 'COMMIT';
        $this->exec();
    }

    public function rollbackTransaction() {
        $this->sql = 'ROLLBACK';
        $this->exec();
    }

    public function showSQL($sql = null) {
        if ($sql !== null) {
            $nsql = $sql;
        } else {
            $nsql = $this->sql . '';
        }
        $colors = array(
            'SELECT' => 'green', 'FROM' => 'green', 'WHERE' => 'green', 'AND' => 'green',
            'UPDATE' => 'green', 'SET' => 'green', 'DELETE' => 'green',
            'ORDER BY' => 'blue', 'GROUP BY' => 'green',
            'LIKE' => 'yellow', 'JOIN' => 'yellow', 'LEFT' => 'yellow',
            'DESC' => 'magenta', 'LIMIT' => 'magenta', '%' => 'red',
        );
        $out = '<style>#sqlback {font-size:12px; background: black; color: white; padding: 10px; font-family: Courier New, Courier, monospace;}
                       #sqlback span.green {color:#00FF00;}
                       #sqlback span.red {color:#FB4F53;}
                       #sqlback span.yellow {color:#FFFF80;}
                       #sqlback span.blue {color:#80FFFF;}
                       #sqlback span.magenta {color:#FF80C0;}</style>' . "\n";

        foreach ($colors as $word => $class) {
            $nsql = str_replace($word, "<span class='$class'>$word</span>", $nsql);
        }
        $out .= "<div id='sqlback'>\n" . nl2br($nsql) . "</div>\n";
        header('Content-Type: text/html; charset=utf-8');
        echo $out;
        return false;
    }

    public function getDebugInfo() {
        return array('queries' => $this->cnt_queries, 'worktime' => $this->cnt_worktime_all);
    }

    public function getDebugLast() {
        return $this->cnt_worktime_this;
    }

    public function getDebugInfoText() {
        $data = $this->getDebugInfo();
        return "SQL-запросов: {$data['queries']}, время MySQL: " . substr($this->cnt_worktime_all, 0, 6) . ' c.';
    }

}
?>