<?php

use app\db\MyDB;

class Auth
{
    const SECRET_STRING = 'И вновь продолжается бой. И гёл. Если очень захотеть, можно в космос полететь, и на Марсе будут яблони цвести';
    /** @var MyDB|null */
    private $db;
    private $key_lifetime_hours = 2592000; //3600 * 24 * 30;
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
     */
    public function __construct(MyDB $db)
    {
        $this->db = $db;
        $this->session = session_id();
        $this->getKey();
        $this->meta['uri'] = trim($_SERVER['REQUEST_URI']);
        $this->meta['host'] = trim(isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : 'undef');
        $this->meta['ip'] = trim(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'undef');
        $this->meta['browser'] = trim(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'undef');
    }

    public function setService($service = 'web')
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

    public function getKey()
    {
        if (isset($_COOKIE['apikey'])) {
            $this->key = trim($_COOKIE['apikey']);
        } else {
            $this->key = md5(uniqid() . self::SECRET_STRING);
            if (!setcookie('apikey', $this->key, time() + $this->key_lifetime_hours, '/')) {
                $this->key = 'x_' . $this->key;
            }
        }
        return $this->key;
    }

    /**
     * @param string $service
     */
    public function checkSession($service = 'web')
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
        if ($row['expired'] == 1) {
            $this->db->sql = "DELETE FROM $dba
                              WHERE au_key = :key LIMIT 1";
            $this->db->execute(
                [
                    ':key' => $this->key,
                ]
            );
        }
        if (isset($row['au_session']) && $row['au_session'] == $this->session) {
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
            setcookie('apikey', $this->key, time() + $this->key_lifetime_hours, '/');
        } elseif (isset($row['au_session']) && $row['au_session'] != $this->session) {
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
            setcookie('apikey', $this->key, time() + $this->key_lifetime_hours, '/');
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

    public function checkMailPassword($email, $password)
    {
        $dbu = $this->db->getTableName('users');
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "SELECT us_id, us_email, us_passwrd, us_name
                            FROM $dbu
                            WHERE us_email = :us_email
                                AND us_passwrd = :us_passwrd
                                AND us_active = 1";
        $this->db->execute(
            [
                ':us_email' => $email,
                ':us_passwrd' => md5($password),
            ]
        );
        $row = $this->db->fetch();
        if (!empty($row['us_id'])) {
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
        return false;
    }

    public function checkPassword($login, $password)
    {
        $dbu = $this->db->getTableName('users');
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "SELECT us_id, us_login, us_email, us_passwrd, us_name, us_admin
                            FROM $dbu
                            WHERE us_login = :us_login
                                AND us_passwrd = :us_passwrd
                                AND us_active = 1";
        $this->db->execute(
            [
                ':us_login' => $login,
                ':us_passwrd' => md5($password),
            ]
        );
        $row = $this->db->fetch();
        if (!empty($row['us_id'])) {
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

        return false;
    }

    public function checkKey($key): bool
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

    public function refreshKey($key)
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

    public function deleteKey()
    {
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "DELETE FROM $dba WHERE au_key = :key";
        $this->db->execute(
            [
                ':key' => $this->key,
            ]
        );
    }
}
