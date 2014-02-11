<?php

class Auth {

    private $db = null;
    private $key_lifetime_hours = 2592000; //3600 * 24 * 30;
    public $key = null;
    private $session = null;
    private $meta = array(
        'uri' => null,
        'host' => null,
        'ip' => null,
        'browser' => null,
    );
    public $user_id = null;
    public $username = null;
    private $secretstring = 'И вновь продолжается бой. И гёл. Если очень захотеть, можно в космос полететь, и на Марсе будут яблони цвести';

    public function __construct($db = null) {
        if ($db === null)
            $this->db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);
        else
            $this->db = $db;
        $this->session = session_id();
        $this->getKey();
        $this->meta['uri'] = $this->db->getEscapedString($_SERVER['REQUEST_URI']);
        $this->meta['host'] = $this->db->getEscapedString(@$_SERVER['REMOTE_HOST']);
        $this->meta['ip'] = $this->db->getEscapedString(@$_SERVER['REMOTE_ADDR']);
        $this->meta['browser'] = $this->db->getEscapedString(@$_SERVER['HTTP_USER_AGENT']);
    }

    public function setService($service = 'web') {
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "UPDATE $dba SET au_service = '$service'
                          WHERE au_key = '$this->key' LIMIT 1";
        $this->db->exec();
    }

    public function getKey() {
        if (isset($_COOKIE['apikey'])) {
            $this->key = $this->db->getEscapedString($_COOKIE['apikey']);
        } else {
            $this->key = md5(uniqid() . $this->secretstring);
            if (!setcookie('apikey', $this->key, time() + $this->key_lifetime_hours, '/'))
                $this->key = + 'x_';
        }
        return $this->key;
    }

    public function checkSession($service = 'web') {
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "SELECT au_session FROM $dba WHERE au_key = '$this->key' AND au_date_expire > now() AND au_service = '$service' LIMIT 1";
        $this->db->exec();
        $row = $this->db->fetch();
        if (isset($row['au_session']) && $row['au_session'] == $this->session) {
            $this->db->sql = "UPDATE $dba
                                SET au_date_last_act = now(),
                                au_date_expire = DATE_ADD(now(),INTERVAL $this->key_lifetime_hours SECOND),
                                au_last_act = '{$this->meta['uri']}'
                              WHERE au_key = '$this->key' LIMIT 1";
            $this->db->exec();
            setcookie('apikey', $this->key, time() + $this->key_lifetime_hours, '/');
        } elseif (isset($row['au_session']) && $row['au_session'] != $this->session) {
            $this->db->sql = "UPDATE $dba
                                SET au_date_last_act = now(),
                                au_date_expire = DATE_ADD(now(),INTERVAL $this->key_lifetime_hours SECOND),
                                au_last_act = '{$this->meta['uri']}',
                                au_session = '$this->session'
                              WHERE au_key = '$this->key' LIMIT 1";
            $this->db->exec();
            setcookie('apikey', $this->key, time() + $this->key_lifetime_hours, '/');
        } else {
            $this->db->sql = "INSERT INTO $dba
                                SET au_date_last_act = now(), au_date_login = now(),
                                au_date_expire = DATE_ADD(now(),INTERVAL $this->key_lifetime_hours SECOND),
                                au_last_act = '{$this->meta['uri']}', au_service = '$service', au_browser = '{$this->meta['browser']}', au_ip = '{$this->meta['ip']}',
                                au_session = '$this->session', au_key = '$this->key'";
            $this->db->exec();
        }
    }

    public function checkMailPassword($email, $password) {
        $dbu = $this->db->getTableName('users');
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "SELECT us_id, us_email, us_passwrd, us_name FROM $dbu WHERE us_active = '1'";
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            if ($row['us_email'] == $email) {
                if ($row['us_passwrd'] == md5($password)) {
                    $this->db->sql = "DELETE FROM $dba WHERE au_us_id = '{$row['us_id']}'";
                    $this->db->exec();
                    $this->db->sql = "UPDATE $dba SET au_us_id = '{$row['us_id']}' WHERE au_key = '$this->key'";
                    $this->db->exec();

                    $this->user_id = $row['us_id'];
                    $this->username = $row['us_name'];
                    $_SESSION['user_id'] = $row['us_id'];
                    $_SESSION['user_name'] = $row['us_name'];
                    $_SESSION['user_auth'] = $this->key;
                    return $this->key;
                    break;
                }
            }
        }
        return false;
    }

    public function checkPassword($login, $password) {
        $dbu = $this->db->getTableName('users');
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "SELECT us_id, us_login, us_email, us_passwrd, us_name, us_admin FROM $dbu WHERE us_active = '1'";
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            if ($row['us_login'] == $login) {
                if ($row['us_passwrd'] == md5($password)) {
                    $this->db->sql = "DELETE FROM $dba WHERE au_us_id = '{$row['us_id']}'";
                    $this->db->exec();
                    $this->db->sql = "UPDATE $dba SET au_us_id = '{$row['us_id']}' WHERE au_key = '$this->key'";
                    $this->db->exec();
                    $this->user_id = $row['us_id'];
                    $this->username = $row['us_name'];
                    $_SESSION['user_id'] = $row['us_id'];
                    $_SESSION['user_name'] = $row['us_name'];
                    $_SESSION['user_admin'] = $row['us_admin'];
                    $_SESSION['user_auth'] = $this->key;
                    return $this->key;
                    break;
                }
            }
        }
        return false;
    }

    public function checkKey($key) {
        $db = $this->db;
        $dba = $db->getTableName('authorizations');
        $db->sql = "SELECT au_key, au_session FROM $dba WHERE au_date_expire > now()";
        $db->exec();
        while ($row = $db->fetch()) {
            if ($row['au_key'] == $key && session_id() == $row['au_session'])
                return true;
        }
        return false;
    }

    public function refreshKey($key) {
        $dba = $this->db->getTableName('authorizations');
        $last_act_script = $_SERVER['REQUEST_URI'];
        $this->db->sql = "UPDATE $dba
                    SET au_date_last_act=now(), au_last_act='$last_act_script', au_date_expire = DATE_ADD(now(),INTERVAL 1 HOUR)
                    WHERE au_key='$key'";
        $this->db->exec();
    }

    public function deleteKey() {
        $dba = $this->db->getTableName('authorizations');
        $this->db->sql = "DELETE FROM $dba WHERE au_key='$this->key'";
        $this->db->exec();
    }

}

?>
