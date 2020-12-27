<?php

namespace app\db;

use app\db\exceptions\AccessException;
use app\db\exceptions\DuplicateKeyException;
use app\db\exceptions\MyPDOException;
use app\db\exceptions\TableException;
use app\db\exceptions\TooManyConnectionsException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Обёртка для работы с БД
 */
class MyDB
{
    private const OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 600,
    ];

    /**
     * @var string|null
     */
    protected $prefix;

    /**
     * @var string
     */
    private $_sql;

    /**
     * @var string
     */
    private $dbDSN;

    /**
     * @var string
     */
    private $dbUser;

    /**
     * @var string
     */
    private $dbPassword;

    /**
     * @var PDO
     */
    private $pdo;

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
     * @param string $dbHost
     * @param string $dbUser
     * @param string $dbPwd
     * @param string $dbBase
     * @param string|null $dbPrefix
     */
    public function __construct(string $dbHost, string $dbUser, string $dbPwd, string $dbBase, string $dbPrefix = null)
    {
        $this->startTimestamp = microtime(true);
        $this->dbDSN = 'mysql:host=' . $dbHost . ';' . 'dbname=' . $dbBase . ';charset=utf8';
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPwd;
        $this->prefix = $dbPrefix;
    }

    /**
     * @return PDO
     * @throws MyPDOException
     */
    private function getPDO(): PDO
    {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO($this->dbDSN, $this->dbUser, $this->dbPassword, self::OPTIONS);
                $this->time = microtime(true) - $this->startTimestamp;
            } catch (PDOException $e) {
                $this->makeException($e);
            }
        }

        return $this->pdo;
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    public function getTableName(string $alias): string
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
     * @throws MyPDOException
     */
    public function getEscapedString(string $text): string
    {
        $out = $this->getPDO()->quote($text);

        if ($this->_cfg_trim_quotes) {
            $out = trim($out, "'");
        }

        return $out;
    }

    /**
     * Подготавливаем запрос
     *
     * @param string|null $sql
     *
     * @throws MyPDOException
     */
    public function prepare(string $sql = ''): void
    {
        if (!empty($sql)) {
            $this->_sql = $sql;
        }

        $this->time = microtime(true) - $this->startTimestamp;
        try {
            $this->_stm = $this->getPDO()->prepare($this->_sql);
            $this->time = microtime(true) - $this->startTimestamp;
        } catch (PDOException $e) {
            $this->makeException($e);
        }
    }

    /**
     * Привязываем параметры по одному
     *
     * @param string $key
     * @param mixed $value
     */
    public function bind(string $key, $value): void
    {
        $this->_stm_params[$key] = $value;
    }

    /**
     * Выполняем PDO::execute
     * @param array $params
     * @return PDOStatement
     * @throws DuplicateKeyException
     * @throws MyPDOException
     */
    public function execute(array $params = [])
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
            $this->_last_inserted_id = $this->getPDO()->lastInsertId();
            $this->_stat_queries_cnt++;
            $this->_stat_worktime_last = microtime(true) - $timer_start;
            $this->_stat_worktime_all += $this->_stat_worktime_last;
            $this->time = microtime(true) - $this->startTimestamp;

            return $this->_stm;
        } catch (PDOException $e) {
            $this->makeException($e);
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

            $this->_stm = $this->getPDO()->query($this->_sql);

            if (is_object($this->_stm)) {
                $this->_affected_rows = $this->_stm->rowCount();
            }
            $this->_last_inserted_id = $this->getPDO()->lastInsertId();
            $this->_stat_queries_cnt++;
            $this->_stat_worktime_last = microtime(true) - $timer_start;
            $this->_stat_worktime_all += $this->_stat_worktime_last;
            $this->time = microtime(true) - $this->startTimestamp;

            return $this->_stm;
        } catch (PDOException $e) {
            $this->makeException($e);
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
            $out = $this->_stm->fetch(PDO::FETCH_ASSOC);
            if (!$out) {
                $out = null;
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
    public function fetchAll($res = null): array
    {
        $this->time = microtime(true) - $this->startTimestamp;
        $out = [];
        try {
            $out = $this->_stm->fetchAll();
            $this->_stm->closeCursor();
        } catch (PDOException $exception) {
            $this->makeException($exception);
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
    public function getAffectedRows(): int
    {
        return $this->_affected_rows;
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->getPDO()->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commitTransaction()
    {
        return $this->getPDO()->commit();
    }

    /**
     * @return bool
     */
    public function rollbackTransaction()
    {
        return $this->getPDO()->rollBack();
    }

    /**
     * @param string|null $sql
     */
    public function showSQL($sql = null): void
    {
        if ($sql !== null) {
            $this->_sql = $sql;
        }
        $output = $this->_sql;

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
            $output = str_replace($word, "<span class='$class'>$word</span>", $output);
        }
        $out .= "<div id='sqlback'>\n" . nl2br($output) . "</div>\n";
        header('Content-Type: text/html; charset=utf-8');
        echo $out;
    }

    /**
     * @return array
     */
    public function getDebugInfo(): array
    {
        return ['queries' => $this->_stat_queries_cnt, 'worktime' => $this->_stat_worktime_all];
    }

    /**
     * @param string $name
     * @param string|int|bool|null $value
     */
    public function __set(string $name, $value)
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
    public function __isset(string $name): bool
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
     * @return string|null
     */
    public function __get(string $name): ?string
    {
        $result = null;

        if ($name === 'sql') {
            $result = $this->_sql;
        }

        return $result;
    }

    /**
     * @param PDOException $exception
     *
     * @throws DuplicateKeyException
     * @throws MyPDOException
     */
    private function makeException(PDOException $exception): void
    {
        $this->time = microtime(true) - $this->startTimestamp;
        $this->_errors[] = $exception->getMessage();

        $errorCode = null !== $exception->errorInfo
            ? $exception->errorInfo[1]
            : $exception->getCode();

        if ($errorCode === 1040) {
            throw new TooManyConnectionsException('Ошибка PDO: to many connections', $errorCode, $exception);
        }
        if (in_array($errorCode, [1044, 1045], true)) {
            throw new AccessException('Ошибка PDO: access denied', $errorCode, $exception);
        }
        if ($errorCode === 1046) {
            throw new TableException('Ошибка PDO: table not found', $errorCode, $exception);
        }
        if ($errorCode === 1054) {
            throw new TableException('Ошибка PDO: Unknown column in Field List', $errorCode, $exception);
        }
        if ($errorCode === 1062) {
            throw new DuplicateKeyException('Ошибка PDO: duplicate key', $errorCode, $exception);
        }

        throw new MyPDOException('Ошибка PDO: ' . $errorCode, $errorCode, $exception);
    }

    /**
     * @param PDO $pdo
     */
    public function setPDO(PDO $pdo): void
    {
        $this->pdo = $pdo;
    }
}
