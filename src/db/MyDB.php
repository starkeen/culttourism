<?php

namespace app\db;

use app\db\exceptions\AccessException;
use app\db\exceptions\DeadLockException;
use app\db\exceptions\DuplicateKeyException;
use app\db\exceptions\FieldException;
use app\db\exceptions\MyPDOException;
use app\db\exceptions\ServerException;
use app\db\exceptions\ServerGoneAwayException;
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

    protected ?string $prefix = null;

    private ?string $_sql = null;

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

    /**
     * @var PDOStatement
     */
    private $_stm;

    /**
     * @var array
     */
    private $_stm_params = [];

    private $lastInsertId;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var float
     */
    private $statWorktimeLast = 0;

    /**
     * @var float
     */
    private $statWorktimeAll = 0;

    /**
     * @var float
     */
    private $startTimestamp;

    /**
     * @var float
     */
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
    public function getPDO(): PDO
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

        return trim($out, '\'');
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
    public function execute(array $params = []): ?PDOStatement
    {
        if (!empty($params)) {
            $this->_stm_params = $params;
        }
        $this->time = microtime(true) - $this->startTimestamp;
        try {
            $timer_start = microtime(true);
            $this->prepare();
            $this->_stm->execute($this->_stm_params);

            $this->lastInsertId = $this->getPDO()->lastInsertId();
            $this->statWorktimeLast = microtime(true) - $timer_start;
            $this->statWorktimeAll += $this->statWorktimeLast;
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
    public function exec($data = null): PDOStatement
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

            $this->lastInsertId = $this->getPDO()->lastInsertId();
            $this->statWorktimeLast = microtime(true) - $timer_start;
            $this->statWorktimeAll += $this->statWorktimeLast;
            $this->time = microtime(true) - $this->startTimestamp;

            return $this->_stm;
        } catch (PDOException $e) {
            $this->makeException($e);
        }
    }

    /**
     * @return mixed|null
     */
    public function fetch()
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
            $this->errors[] = $e->getMessage();
        }

        return $out;
    }

    /**
     * @return mixed
     */
    public function fetchCol()
    {
        $this->time = microtime(true) - $this->startTimestamp;

        return $this->_stm->fetchColumn();
    }

    /**
     * @return array
     * @throws MyPDOException
     */
    public function fetchAll(): array
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
     * @param string $sql
     */
    public function setSQL(string $sql): void
    {
        $this->sql = $sql;
    }

    /**
     * @return string
     */
    public function getSQL(): string
    {
        return $this->sql;
    }

    /**
     * @return mixed
     */
    public function getLastInserted()
    {
        return $this->lastInsertId;
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
        $this->errors[] = $exception->getMessage();

        $errorCode = null !== $exception->errorInfo && isset($exception->errorInfo[1])
            ? $exception->errorInfo[1]
            : $exception->getCode();

        if ($errorCode === 1040) {
            throw new TooManyConnectionsException('Ошибка PDO: to many connections', $errorCode, $exception);
        }
        if (in_array($errorCode, [1044, 1045], true)) {
            throw new AccessException('Ошибка PDO: access denied', $errorCode, $exception);
        }
        if ($errorCode === 1054) {
            throw new TableException('Ошибка PDO: Unknown column in Field List', $errorCode, $exception);
        }
        if ($errorCode === 1062) {
            throw new DuplicateKeyException('Ошибка PDO: duplicate key', $errorCode, $exception);
        }
        if ($errorCode === 1146) {
            throw new TableException('Ошибка PDO: table not found', $errorCode, $exception);
        }
        if ($errorCode === 1205) {
            throw new DeadLockException('Ошибка PDO: lock timeout', $errorCode, $exception);
        }
        if ($errorCode === 1213) {
            throw new DeadLockException('Ошибка PDO: deadlock', $errorCode, $exception);
        }
        if ($errorCode === 1406) {
            throw new FieldException('Ошибка PDO: data too long for column', $errorCode, $exception);
        }
        if ($errorCode === 2002) {
            throw new ServerException('Ошибка PDO: server unavailable', $errorCode, $exception);
        }
        if ($errorCode === 2006) {
            throw new ServerGoneAwayException('Ошибка PDO: server has gone away', $errorCode, $exception);
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
