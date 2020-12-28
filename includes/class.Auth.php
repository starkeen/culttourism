<?php

use app\core\CookieStorage;
use app\db\MyDB;

class Auth
{
    private const SECRET_STRING = 'И вновь продолжается бой. И гёл. Если очень захотеть, можно в космос полететь, и на Марсе будут яблони цвести';

    private const COOKIE_KEY = 'apikey';

    /**
     * @var MyDB
     */
    private $db;

    /**
     * @var CookieStorage
     */
    private $cookieStorage;

    private $key_lifetime_hours = 2592000; // это 30 дней
    public $key;
    private $session;
    private $meta = [
        'uri' => null,
        'host' => null,
        'ip' => null,
        'browser' => null,
    ];
    public $user_id;
    public $username;

    /**
     * @param MyDB $db
     * @param CookieStorage $cookieStorage
     */
    public function __construct(MyDB $db, CookieStorage $cookieStorage)
    {
        $this->db = $db;
        $this->cookieStorage = $cookieStorage;

        $this->session = session_id();
        $this->getKey();
        $this->meta['uri'] = trim($_SERVER['REQUEST_URI']);
        $this->meta['host'] = trim($_SERVER['REMOTE_HOST'] ?? 'undef');
        $this->meta['ip'] = trim($_SERVER['REMOTE_ADDR'] ?? 'undef');
        $this->meta['browser'] = trim($_SERVER['HTTP_USER_AGENT'] ?? 'undef');

        $this->meta['browser'] = mb_substr($this->meta['browser'], 0, 50);
    }

    /**
     * @param string $service
     */
    public function setService($service = 'web'): void
    {
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "UPDATE $dba SET au_service = :service
                          WHERE au_key = :key LIMIT 1";
        $this->db->execute(
            [
                ':key' => $this->key,
                ':service' => $service,
            ]
        );
    }

    /**
     * @return string
     */
    private function getKey(): string
    {
        $apiKey = $this->cookieStorage->getCookieValue(self::COOKIE_KEY);
        if ($apiKey !== null) {
            $this->key = $apiKey;
        } else {
            $this->key = $this->getRandom();
            $this->cookieStorage->setCookie(self::COOKIE_KEY, $this->key, $this->key_lifetime_hours);
        }

        return $this->key;
    }

    /**
     * @param string $service
     */
    public function checkSession($service = 'web'): void
    {
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "SELECT au_session, IF(au_date_expire < NOW(), 1, 0) AS expired FROM $dba
                            WHERE au_key = :key
                            LIMIT 1";
        $this->db->execute(
            [
                ':key' => (string) $this->key,
            ]
        );
        $row = $this->db->fetch();
        if ($row !== null && (int) $row['expired'] === 1) {
            $this->db->sql = "DELETE FROM $dba
                              WHERE au_key = :key LIMIT 1";
            $this->db->execute(
                [
                    ':key' => $this->key,
                ]
            );
        }
        if (isset($row['au_session']) && $row['au_session'] === $this->session) {
            $this->db->sql = "UPDATE $dba
                                SET au_date_last_act = NOW(),
                                au_date_expire = DATE_ADD(now(),INTERVAL :key_lifetime_hours SECOND),
                                au_last_act = :uri
                              WHERE au_key = :key
                              LIMIT 1";
            $this->db->execute(
                [
                    ':key' => $this->key,
                    ':key_lifetime_hours' => $this->key_lifetime_hours,
                    ':uri' => $this->meta['uri'],
                ]
            );
            $this->cookieStorage->setCookie(self::COOKIE_KEY, $this->key, $this->key_lifetime_hours);
        } elseif (isset($row['au_session']) && $row['au_session'] !== $this->session) {
            $this->db->sql = "UPDATE $dba
                                SET au_date_last_act = NOW(),
                                au_date_expire = DATE_ADD(now(),INTERVAL :key_lifetime_hours SECOND),
                                au_last_act = :uri,
                                au_session = :session
                              WHERE au_key = :key LIMIT 1";
            $this->db->execute(
                [
                    ':key' => $this->key,
                    ':key_lifetime_hours' => $this->key_lifetime_hours,
                    ':uri' => $this->meta['uri'],
                    ':session' => $this->session,
                ]
            );
            $this->cookieStorage->setCookie(self::COOKIE_KEY, $this->key, $this->key_lifetime_hours);
        } else {
            $this->db->sql = "INSERT INTO $dba
                                SET au_date_last_act = NOW(), au_date_login = NOW(),
                                    au_date_expire = DATE_ADD(NOW(), INTERVAL :key_lifetime_hours SECOND),
                                    au_us_id = 0,
                                    au_last_act = :uri,
                                    au_service = :service,
                                    au_browser = :browser,
                                    au_ip = :ip,
                                    au_session = :session,
                                    au_key = :key
                                ON DUPLICATE KEY UPDATE
                                    au_date_last_act = NOW(),
                                    au_date_expire = DATE_ADD(now(),INTERVAL :key_lifetime_hours2 SECOND),
                                    au_last_act = :uri2";
            $this->db->execute(
                [
                    ':key' => $this->key,
                    ':key_lifetime_hours' => $this->key_lifetime_hours,
                    ':key_lifetime_hours2' => $this->key_lifetime_hours,
                    ':uri' => $this->meta['uri'],
                    ':uri2' => $this->meta['uri'],
                    ':session' => $this->session,
                    ':service' => $service,
                    ':browser' => $this->meta['browser'],
                    ':ip' => $this->meta['ip'],
                ]
            );
        }
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return null|string
     */
    public function checkMailPassword(string $email, string $password): ?string
    {
        $dbu = $this->db->getTableName('users');
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "SELECT us_id, us_email, us_passwrd, us_name
                            FROM $dbu
                            WHERE us_email = :us_email
                                AND us_active = 1";
        $this->db->execute(
            [
                ':us_email' => $email,
            ]
        );
        $row = $this->db->fetch();
        if ($row !== null && !empty($row['us_id']) && password_verify($password, $row['us_passwrd'])) {
            $this->db->sql = "DELETE FROM $dba WHERE au_us_id = :usid";
            $this->db->execute(
                [
                    ':usid' => $row['us_id'],
                ]
            );
            $this->db->sql = "UPDATE $dba SET au_us_id = :usid WHERE au_key = :key";
            $this->db->execute(
                [
                    ':usid' => $row['us_id'],
                    ':key' => $this->key,
                ]
            );

            $this->user_id = $row['us_id'];
            $this->username = $row['us_name'];
            $_SESSION['user_id'] = $row['us_id'];
            $_SESSION['user_name'] = $row['us_name'];
            $_SESSION['user_auth'] = $this->key;

            return $this->key;
        }

        return null;
    }

    /**
     * @param string $login
     * @param string $password
     *
     * @return string|null
     */
    public function checkPassword(string $login, string $password): ?string
    {
        $dbu = $this->db->getTableName('users');
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "SELECT us_id, us_login, us_email, us_passwrd, us_name, us_admin
                            FROM $dbu
                            WHERE us_login = :us_login
                                AND us_active = 1";
        $this->db->execute(
            [
                ':us_login' => $login,
            ]
        );
        $row = $this->db->fetch();
        if ($row !== null && !empty($row['us_id']) && password_verify($password, $row['us_passwrd'])) {
            $this->db->sql = "DELETE FROM $dba WHERE au_us_id = :usid";
            $this->db->execute(
                [
                    ':usid' => $row['us_id'],
                ]
            );

            $this->db->sql = "UPDATE $dba SET au_us_id = :usid WHERE au_key = :key";
            $this->db->execute(
                [
                    ':usid' => $row['us_id'],
                    ':key' => $this->key,
                ]
            );

            $this->user_id = $row['us_id'];
            $this->username = $row['us_name'];
            $_SESSION['user_id'] = $row['us_id'];
            $_SESSION['user_name'] = $row['us_name'];
            $_SESSION['user_admin'] = $row['us_admin'];
            $_SESSION['user_auth'] = $this->key;

            return $this->key;
        }

        return null;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function checkKey(string $key): bool
    {
        $db = $this->db;
        $dba = $db->getTableName('authorizations');
        $db->sql = "SELECT au_key, au_session
                    FROM $dba
                    WHERE au_date_expire > NOW()
                        AND au_key = :key
                        AND au_session = :session";
        $this->db->execute(
            [
                ':session' => session_id(),
                ':key' => $key,
            ]
        );
        while ($row = $db->fetch()) {
            if ($row['au_key'] === $key && session_id() === $row['au_session']) {
                return true;
            }
        }
        $db->sql = "INSERT IGNORE INTO $dba SET
                        au_key = :key,
                        au_us_id = 0,
                        au_session = :session,
                        au_date_expire = DATE_ADD(now(), INTERVAL 1 HOUR)";
        $this->db->execute(
            [
                ':session' => session_id(),
                ':key' => $key,
            ]
        );

        return false;
    }

    /**
     * @param string $key
     */
    public function refreshKey(string $key): void
    {
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "UPDATE $dba SET
                                au_date_last_act = NOW(),
                                au_last_act = :last_act_script,
                                au_date_expire = DATE_ADD(now(), INTERVAL 1 HOUR)
                            WHERE au_key = :key";
        $this->db->execute(
            [
                ':last_act_script' => $_SERVER['REQUEST_URI'],
                ':key' => $key,
            ]
        );
    }

    /**
     * Удаляет ключ авторизации
     */
    public function deleteKey(): void
    {
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "DELETE FROM $dba WHERE au_key = :key";
        $this->db->execute(
            [
                ':key' => $this->key,
            ]
        );
    }

    /**
     * @return string
     */
    private function getRandom(): string
    {
        return password_hash(uniqid('ct', true) . self::SECRET_STRING, PASSWORD_BCRYPT);
    }
}
