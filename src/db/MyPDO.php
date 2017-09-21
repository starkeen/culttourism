<?php

namespace app\db;

use app\exceptions\MyPDOException;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

/**
 *
 * @author Andrey_Panisko
 */
class MyPDO implements IDB
{
    private static $_instances = false;

    public $link;
    protected $prefix;
    /** @var string */
    private $_sql;
    /** @var PDO */
    private $_pdo;
    /** @var PDOStatement */
    private $_stm;
    private $_stm_params = [];
    private $_affected_rows = 0;
    private $_last_inserted_id;
    private $_errors = [];
    private $_cfg_trim_quotes = true;
    private $_stat_queries_cnt = 0;
    private $_stat_worktime_last = 0;
    private $_stat_worktime_all = 0;

    /** @var float */
    private $startTimestamp;
    /** @var float */
    private $time;

    /**
     * @param string      $db_host
     * @param string      $db_user
     * @param string      $db_pwd
     * @param string      $db_base
     * @param string|null $db_prefix
     *
     * @throws MyPDOException
     */
    public function __construct($db_host, $db_user, $db_pwd, $db_base, $db_prefix = null)
    {
        $this->startTimestamp = microtime(true);

        if (!self::$_instances) {
            $opts = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_TIMEOUT => 600,
                //PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            ];
            $this->prefix = $db_prefix;
            try {
                $dsn = "mysql:host=$db_host;dbname=$db_base;charset=utf8";
                $this->_pdo = new PDO($dsn, $db_user, $db_pwd, $opts);
                self::$_instances = true;
                $this->link = true;
                $this->time = microtime(true) - $this->startTimestamp;
            } catch (PDOException $e) {
                $this->time = microtime(true) - $this->startTimestamp;
                $this->_errors[] = $e->getMessage();
                throw new MyPDOException('Ошибка соединения с БД', 0, $e);
            }
        }
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    public function getTableName($alias): string
    {
        $result = '`' . $alias . '`';
        if ($this->prefix !== null) {
            $result = '`' . $this->prefix . '_' . $alias . '`';
        }

        return $result;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function getEscapedString($text): string
    {
        $out = $this->_pdo->quote($text);

        if ($this->_cfg_trim_quotes) {
            $out = trim($out, "'");
        }

        return $out;
    }

    /**
     * Подготавливаем запрос
     *
     * @param string $sql
     * @throws MyPDOException
     */
    public function prepare($sql = '')
    {
        if (!empty($sql)) {
            $this->_sql = $sql;
        }

        $this->time = microtime(true) - $this->startTimestamp;
        try {
            $this->_stm = $this->_pdo->prepare($this->_sql);
            $this->time = microtime(true) - $this->startTimestamp;
        } catch (PDOException $e) {
            throw new MyPDOException('Ошибка PDO:prepare', 0, $e);
        }
    }

    /**
     * Привязываем параметры по одному
     *
     * @param string $key
     * @param mixed  $value
     */
    public function bind($key, $value)
    {
        $this->_stm_params[$key] = $value;
    }

    /**
     * Выполняем PDO::execute
     * @return PDOStatement
     * @throws MyPDOException
     */
    public function execute($params = [])
    {
        if (!empty($params)) {
            $this->_stm_params = $params;
        }
        $this->time = microtime(true) - $this->startTimestamp;
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
            $this->time = microtime(true) - $this->startTimestamp;

            return $this->_stm;
        } catch (PDOException $e) {
            $this->time = microtime(true) - $this->startTimestamp;
            $this->_errors[] = $e->getMessage();

            throw new MyPDOException('Ошибка PDO:execute', 0, $e);
        }
    }

    /**
     * Выполняем PDO::query - совместимость со старыми вызовами
     *
     * @param mixed $data
     *
     * @return PDOStatement
     *
     * @throws MyPDOException
     */
    public function exec($data = null)
    {
        if ($data !== null) {
            if (is_string($data)) {
                $this->_sql = $data;
            } elseif (is_array($data)) {
                $this->_stm_params = $data;
            }
        }
        $this->time = microtime(true) - $this->startTimestamp;
        try {
            $timer_start = microtime(true);

            $this->_stm = $this->_pdo->query($this->_sql);

            if (is_object($this->_stm)) {
                $this->_affected_rows = $this->_stm->rowCount();
            }
            $this->_last_inserted_id = $this->_pdo->lastInsertId();
            $this->_stat_queries_cnt++;
            $this->_stat_worktime_last = microtime(true) - $timer_start;
            $this->_stat_worktime_all += $this->_stat_worktime_last;
            $this->time = microtime(true) - $this->startTimestamp;
            return $this->_stm;
        } catch (PDOException $e) {
            $this->time = microtime(true) - $this->startTimestamp;
            $this->_errors[] = $e->getMessage();

            throw new MyPDOException('Ошибка PDO:execute', 0, $e);
        }
    }

    /**
     * @param null $res
     *
     * @return mixed|null
     */
    public function fetch($res = null)
    {
        $out = null;

        try {
            $this->time = microtime(true) - $this->startTimestamp;
            $out = $this->_stm->fetch();
            if (!$out) {
                $this->_stm->closeCursor();
            }
        } catch (PDOException $e) {
            $this->time = microtime(true) - $this->startTimestamp;
            $this->_errors[] = $e->getMessage();
        }

        return $out;
    }

    /**
     * @param null $res
     *
     * @return mixed
     */
    public function fetchCol($res = null)
    {
        $this->time = microtime(true) - $this->startTimestamp;
        return $this->_stm->fetchColumn();
    }

    /**
     * @param null $res
     *
     * @return array
     * @throws MyPDOException
     */
    public function fetchAll($res = null)
    {
        $this->time = microtime(true) - $this->startTimestamp;
        try {
            $out = $this->_stm->fetchAll();
            $this->_stm->closeCursor();
        } catch (Exception $e) {
            $this->time = microtime(true) - $this->startTimestamp;
            $error = $e->getMessage();
            $this->_errors[] = $error;
            throw new MyPDOException($error);
        }
        return $out;
    }

    /**
     * @return mixed
     */
    public function getLastInserted()
    {
        return $this->_last_inserted_id;
    }

    /**
     * @return int
     */
    public function getAffectedRows()
    {
        return $this->_affected_rows;
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->_pdo->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commitTransaction()
    {
        return $this->_pdo->commit();
    }

    /**
     * @return bool
     */
    public function rollbackTransaction()
    {
        return $this->_pdo->rollBack();
    }

    /**
     * @param string $sql
     */
    public function showSQL($sql = null)
    {
        if ($sql !== null) {
            $this->_sql = $sql;
        }
        $nsql = $this->_sql;

        $colors = [
            'SELECT' => 'green',
            'FROM' => 'green',
            'WHERE' => 'green',
            'AND' => 'green',
            'UPDATE' => 'green',
            'SET' => 'green',
            'DELETE' => 'green',
            'ORDER BY' => 'blue',
            'GROUP BY' => 'green',
            'LIKE' => 'yellow',
            'JOIN' => 'yellow',
            'LEFT' => 'yellow',
            'DESC' => 'magenta',
            'LIMIT' => 'magenta',
            '%' => 'red',
        ];
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

    /**
     * @return array
     */
    public function getDebugInfo()
    {
        return ['queries' => $this->_stat_queries_cnt, 'worktime' => $this->_stat_worktime_all];
    }

    /**
     * @return string
     */
    public function getDebugLast()
    {
        return $this->cnt_worktime_this;
    }

    /**
     * @return string
     */
    public function getDebugInfoText(): string
    {
        $data = $this->getDebugInfo();
        return "SQL-запросов: {$data['queries']}, время MySQL: "
            . substr($this->_stat_worktime_all, 0, 6) . ' c.';
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        if ($name === 'sql') {
            $this->_sql = $value;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name): bool
    {
        $result = false;

        if ($name === 'sql') {
            $result = true;
        }

        return $result;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function __get($name)
    {
        $result = null;

        if ($name === 'sql') {
            $result = $this->_sql;
        }

        return $result;
    }
}
