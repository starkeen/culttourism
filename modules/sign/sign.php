<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        [$module_id, $page_id, $id] = $mod;
        parent::__construct($db, 'sign');
        $this->id = $id;
        if ($page_id == 'in') {
            $this->content = $this->getIn();
        } elseif ($page_id == 'up') {
            $this->content = $this->getUp();
        } elseif ($page_id == 'check') {
            $this->content = $this->doCheck($this->id);
        } elseif ($page_id == 'out') {
            $this->content = $this->doOut();
        } elseif ($page_id == 'form') {
            $this->content = $this->getFormLogin();
        } else {
            $this->processError(Core::HTTP_CODE_404);
        }
    }

    private function getIn() {
        $uniq_key = md5(uniqid(mt_rand(), true));
        if (!isset($_SESSION['userkey']) || !$_SESSION['userkey']) {
            $_SESSION['userkey'] = $uniq_key;
        } else {
            $uniq_key = $_SESSION['userkey'];
        }
        $this->smarty->assign('key', $uniq_key);
        $this->smarty->assign('url', _SITE_URL);
        return $this->smarty->fetch(_DIR_TEMPLATES . '/sign/in.sm.html');
    }

    private function getUp() {
        return $this->smarty->fetch(_DIR_TEMPLATES . '/sign/up.sm.html');
    }

    private function doOut() {
        $this->auth->deleteKey();
        $_SESSION['user'] = null;
        $_SESSION['user_id'] = null;
        $_SESSION['user_name'] = null;
        $_SESSION['user_auth'] = null;
        if ($_SERVER['HTTP_REFERER']) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        } else {
            header('Location: /');
            exit();
        }
    }

    private function doCheck($key): void
    {
        if (isset($_SERVER['HTTP_REFERER']) && !isset($_SESSION['user_referer'])) {
            $_SESSION['user_referer'] = $_SERVER['HTTP_REFERER'];
        }
        if (!$key) {
            $this->processError(Core::HTTP_CODE_301, 'sign/in/');
        }
        if (!isset($_POST) || empty($_POST)) {
            $this->processError(Core::HTTP_CODE_301, 'sign/in/');
        }

        $email = trim($_POST['email']);
        $passw = trim($_POST['userpass']);

        if ($this->auth->checkMailPassword($email, $passw)) {
            if (isset($_SESSION['user_referer'])) {
                header('Location: ' . $_SESSION['user_referer']);
                exit();
            } else {
                header('Location: ' . _SITE_URL);
                exit();
            }
        } else {
            $this->processError(Core::HTTP_CODE_301, 'sign/in/');
        }
    }

    private function getFormLogin() {
        if (isset($_SESSION['user_id'])) {
            $this->smarty->assign('username', $_SESSION['user_name']);
            return $this->smarty->fetch(_DIR_TEMPLATES . '/sign/authuser.sm.html');
        } else {
            $this->smarty->assign('baseurl', _SITE_URL);
            $this->smarty->assign('authkey', 'ewtheqryb35yqb356y4ery');
            return $this->smarty->fetch(_DIR_TEMPLATES . '/sign/authform.sm.html');
        }
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
