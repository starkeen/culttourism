<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        global $db;
        global $smarty;
        parent::__construct($db, 'sign');
        $this->id = $id;
        if ($page_id == 'in')
            $this->content = $this->getIn($smarty);
        elseif ($page_id == 'up')
            $this->content = $this->getUp($smarty);
        elseif ($page_id == 'check')
            $this->content = $this->doCheck($this->id);
        elseif ($page_id == 'out')
            $this->content = $this->doOut();
        else
            $this->getError('404');
    }

    private function getIn($smarty) {
        $uniq_key = md5(uniqid(mt_rand(), true));
        if (!isset($_SESSION['userkey']) || !$_SESSION['userkey'])
            $_SESSION['userkey'] = $uniq_key;
        else
            $uniq_key = $_SESSION['userkey'];
        $smarty->assign('key', $uniq_key);
        $smarty->assign('url', _SITE_URL);
        return $smarty->fetch(_DIR_TEMPLATES . '/sign/in.sm.html');
    }

    private function getUp($smarty) {
        return $smarty->fetch(_DIR_TEMPLATES . '/sign/up.sm.html');
    }

    private function doOut() {
        $this->auth->deleteKey();
        $_SESSION['user'] = null;
        $_SESSION['user_id'] = null;
        $_SESSION['user_name'] = null;
        $_SESSION['user_auth'] = null;
        if ($_SERVER['HTTP_REFERER'])
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        else
            header('Location: /');
    }

    private function doCheck($key) {
        if (isset($_SERVER['HTTP_REFERER']) && !isset($_SESSION['user_referer'])) {
            $_SESSION['user_referer'] = $_SERVER['HTTP_REFERER'];
        }
        if (!$key)
            $this->getError('301', 'sign/in/');
        if (!isset($_POST) || empty($_POST))
            $this->getError('301', 'sign/in/');

        $email = cut_trash_string($_POST['email']);
        $passw = cut_trash_string($_POST['userpass']);

        if ($this->auth->checkMailPassword($email, $passw))
            if (isset($_SESSION['user_referer']))
                header('Location: ' . $_SESSION['user_referer']);
            else
                header('Location: ' . _SITE_URL);
        else
            $this->getError('301', 'sign/in/');
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}

?>