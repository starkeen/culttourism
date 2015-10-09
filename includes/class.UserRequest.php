<?php

class UserRequest {

    private $key = '';

    public function __construct($type_id = 1) {
        $this->key = getGUID();
        $owner_id = $_SESSION['user_id'];
        $this->req_type = intval($type_id);
        $db = FactoryDB::db();
        $dbr = $db->getTableName('requests');
        $db->sql = "INSERT INTO $dbr
                    (rq_us_id, rq_keystring, rq_confirmed, rq_request_type, rq_date_create)
                    VALUES
                    ('$owner_id', '$this->key', '0', '$this->req_type', now())";
        return $db->exec();
    }

    public function setParam($param, $value) {
        $db = FactoryDB::db();
        $dbr = $db->getTableName('requests');
        $db->sql = "SELECT rq_attrs FROM $dbr
                    WHERE  rq_keystring = '$this->key'";
        $db->exec();
        $attrs = $db->fetch();
        $attrs = unserialize($attrs['rq_attrs']);
        $attrs[$param] = $value;
        $attrs = serialize($attrs);
        $db->sql = "UPDATE $dbr
                    SET rq_attrs = '$attrs'
                    WHERE  rq_keystring = '$this->key'";
        return $db->exec();
    }

    public function getKey() {
        return $this->key;
    }

    public function getReqLink() {
        return 'https://' . _URL_ROOT . "/request/$this->key/";
    }

}
