<?php

/**
 * Description of MyPDO
 *
 * @author Andrey_Panisko
 */
include 'interfaces/IDB.php';

class MyPDO implements IDB {

    private static $_instances = false;
    public $link = null;
    protected $prefix = null;
    private $_sql = null;
    private $_pdo = null;
    private $_stm = null;
    private $_stm_params = array();
    private $_affected_rows = null;
    private $_last_inserted_id = null;
    private $_errors = array();
    private $_cfg_trim_quotes = true;
    private $_stat_queries_cnt = 0;
    private $_stat_worktime_last = 0;
    private $_stat_worktime_all = 0;

    public function __construct($db_host, $db_user, $db_pwd, $db_base, $db_prefix = null) {
        if (!MyPDO::$_instances) {
            $opts = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    //PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            );
            $this->prefix = $db_prefix;
            try {
                $this->_pdo = new PDO("mysql:host=$db_host;dbname=$db_base;charset=utf8", $db_user, $db_pwd, $opts);
                MyPDO::$_instances = true;
                $this->link = true;
            } catch (PDOException $e) {
                $this->_errors[] = $e->getMessage();
            }
        }
    }

    public function getTableName($alias) {
        if ($this->prefix === null) {
            return '`' . $alias . '`';
        } else {
            return '`' . $this->prefix . '_' . $alias . '`';
        }
    }

    public function getEscapedString($text) {
        $out = $this->_pdo->quote($text);
        if ($this->_cfg_trim_quotes) {
            $out = trim($out, "'");
        }
        return $out;
    }

    /**
     * Подготавливаем запрос
     * @param string $sql
     */
    public function prepare($sql = '') {
        if (!empty($sql)) {
            $this->_sql = $sql;
        }
        $this->_stm = $this->_pdo->prepare($this->_sql);
    }

    /**
     * Привязываем параметры по одному
     * @param string $key
     * @param mixed $value
     */
    public function bind($key, $value) {
        $this->_stm_params[$key] = $value;
        //$this->_stm->bindParam($key, $value);
    }

    /**
     * Выполняем PDO::execute
     */
    public function execute($params = array()) {
        if (!empty($params)) {
            $this->_stm_params = $params;
        }
        try {
            $timer_start = microtime(true);
            $this->prepare();
            $this->_stm->execute($this->_stm_params);
            if (is_object($this->_stm)) {
                $this->_affected_rows = $this->_stm->rowCount();
            }
            $this->_last_inserted_id = $this->_pdo->lastInsertId();
            $this->_stat_queries_cnt++;
            $this->_stat_worktime_last = microtime(true) - $timer_start;
            $this->_stat_worktime_all += $this->_stat_worktime_last;
            return $this->_stm;
        } catch (PDOException $e) {
            $this->_errors[] = $e->getMessage();
            $msg = "SQL-execute error: " . $e->getMessage() . "\n"
                    . 'file: ' . $e->getFile() . ':' . $e->getLine() . "\n"
                    . 'URI: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'undefined') . "\n"
                    . "\n__________________________\n\n\n"
                    . 'SQL: ' . $this->_sql . "\n"
                    . "\n__________________________\n\n\n"
                    . 'trace: ' . $e->getTraceAsString() . "\n";

            mail('starkeen@gmail.com', 'SQL error on ' . _URL_ROOT, $msg);
        }
    }

    /**
     * Выполняем PDO::query - совместимость со старыми вызовами
     * @param mixed $data
     * @return type
     */
    public function exec($data = null) {
        if ($data !== null) {
            if (is_string($data)) {
                $this->sql = $data;
            } elseif (is_array($data)) {
                $this->_stm_params = $data;
            }
        }
        try {
            $timer_start = microtime(true);

            //$this->_stm = $this->_pdo->prepare($this->_sql);
            //$this->_stm->execute($this->_stm_params);
            $this->_stm = $this->_pdo->query($this->_sql);

            if (is_object($this->_stm)) {
                $this->_affected_rows = $this->_stm->rowCount();
            }
            $this->_last_inserted_id = $this->_pdo->lastInsertId();
            $this->_stat_queries_cnt++;
            $this->_stat_worktime_last = microtime(true) - $timer_start;
            $this->_stat_worktime_all += $this->_stat_worktime_last;
            return $this->_stm;
        } catch (PDOException $e) {
            $this->_errors[] = $e->getMessage();
            $msg = "SQL-exec error: " . $e->getMessage() . "\n"
                    . 'file: ' . $e->getFile() . ':' . $e->getLine() . "\n"
                    . 'URI: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'undefined') . "\n"
                    . "\n__________________________\n\n\n"
                    . 'SQL: ' . $this->_sql . "\n"
                    . "\n__________________________\n\n\n"
                    . 'trace: ' . $e->getTraceAsString() . "\n";

            mail('starkeen@gmail.com', 'SQL error on ' . _URL_ROOT, $msg);
        }
    }

    public function fetch($res = null) {
        $out = null;
        try {
            $out = $this->_stm->fetch();
            if (!$out) {
                $this->_stm->closeCursor();
            }
        } catch (PDOException $e) {
            $this->_errors[] = $e->getMessage();
        }
        return $out;
    }

    public function fetchCol($res = null) {
        return $this->_stm->fetchColumn();
    }

    public function fetchAll($res = null) {
        try {
            $out = $this->_stm->fetchAll();
            $this->_stm->closeCursor();
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->_errors[] = $error;
            throw new Exception($error);
        }
        return $out;
    }

    public function getLastInserted() {
        return $this->_last_inserted_id;
    }

    public function getAffectedRows() {
        return $this->_affected_rows;
    }

    public function beginTransaction() {
        $this->_pdo->beginTransaction();
    }

    public function commitTransaction() {
        $this->_pdo->commit();
    }

    public function rollbackTransaction() {
        $this->_pdo->rollBack();
    }

    public function showSQL($sql = null) {
        if ($sql !== null) {
            $this->sql = $sql;
        }
        $nsql = $this->_sql;

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
        return;
    }

    public function getDebugInfo() {
        return array('queries' => $this->_stat_queries_cnt, 'worktime' => $this->_stat_worktime_all);
    }

    public function getDebugLast() {
        return $this->cnt_worktime_this;
    }

    public function getDebugInfoText() {
        $data = $this->getDebugInfo();
        return "SQL-запросов: {$data['queries']}, время MySQL: "
                . substr($this->_stat_worktime_all, 0, 6) . ' c.';
    }

    public function __set($name, $value) {
        if ($name == 'sql') {
            $this->_sql = $value;
        }
    }

    public function __get($name) {
        if ($name == 'sql') {
            return $this->_sql;
        }
    }

}
