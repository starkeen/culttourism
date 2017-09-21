<?php

use app\db\FactoryDB;

class Identify
{
    private $db;
    private $session_id = '';
    private $cookie_id = '';
    private $method = 'web';

    public function __construct($method = 'web')
    {
        $db = FactoryDB::db();
        $this->db = $db;
        $this->method = $method;
        $this->session_id = session_id();
        if (isset($_COOKIE['apikey'])) {
            $this->cookie_id = $db->getEscapedString($_COOKIE['apikey']);
        } else {
            $this->cookie_id = uniqid();
            $_COOKIE['apikey'] = $this->cookie_id;
        }
    }

    public function check()
    {
        $dba = $this->db->getTableName('authorizations');
        $host = @$_SERVER['REMOTE_HOST'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $last_act_script = $_SERVER['REQUEST_URI'];
        $browser = $_SERVER['HTTP_USER_AGENT'];

        $this->db->sql = "INSERT INTO $dba 
                            SET au_session = '$this->session_id', au_key = '$this->cookie_id', au_service = '$this->method',
                                au_date_login = now(), au_date_last_act = now(), au_date_expire = DATE_ADD(now(),INTERVAL " . _AUTH_EXPIRE_HOURS . " HOUR),
                                au_host = '$host', au_browser = '$browser', au_last_act = '$last_act_script', au_ip = '$ip'
                            ON DUPLICATE KEY UPDATE
                                au_date_last_act = now(), au_date_expire = DATE_ADD(now(),INTERVAL " . _AUTH_EXPIRE_HOURS . " HOUR),
                                au_host = '$host', au_browser = '$browser', au_last_act = '$last_act_script', au_ip = '$ip'";
        $this->db->exec();
    }

}
