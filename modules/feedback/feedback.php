<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        parent::__construct($db, 'feedback', $page_id);
        global $db;
        $db = $this->db;
        if ($page_id == '') {
            $this->getCommon();
        } elseif ($page_id == 'getcapt') {
            $this->getCaptcha();
        } elseif ($page_id == 'newpoint') {
            //
        } else {
            $this->getError('404');
        }
    }

    private function getCommon() {
        $data = array(
            'error' => null,
            'success' => null,
            'fname' => null,
            'ftext' => null,
            'fmail' => null,
        );
        if (!isset($_SESSION['feedback_referer']) || $_SESSION['feedback_referer'] == null) {
            $_SESSION['feedback_referer'] = $_SERVER['HTTP_REFERER'];
        }
        $referer = $_SESSION['feedback_referer'];
        if (isset($_POST) && !empty($_POST)) {
            $data['fname'] = cut_trash_text($_POST['fname']);
            $data['fmail'] = cut_trash_text($_POST['fmail']);
            $data['ftext'] = cut_trash_text($_POST['ftext']);
            $fcapt = $_POST['fcapt'];
            $ftextcheck = cut_trash_text($_POST['ftextcheck']);

            if (isset($_SESSION['captcha_keystring']) && $fcapt != $_SESSION['captcha_keystring']) {
                $data['error'] = 'fcapt';
            }
            if ($ftextcheck != '') {
                $data['error'] = 'fcapt';
            }
            if ($data['ftext'] == '') {
                $data['error'] = 'ftext';
            }
            if ($data['fname'] == '') {
                $data['error'] = 'fname';
            }

            if ($data['error'] == null) {
                $data['success'] = true;
                $fb = new MFeedback($this->db);
                $fb->add(array(
                    'fb_name' => $data['fname'],
                    'fb_text' => $data['ftext'],
                    'fb_sendermail' => $data['fmail'],
                    'fb_referer' => $referer,
                    'fb_ip' => $_SERVER['REMOTE_ADDR'],
                    'fb_browser' => $_SERVER['HTTP_USER_AGENT'],
                ));
                $mail_attrs = array(
                    'user_name' => $data['fname'],
                    'user_mail' => $data['fmail'],
                    'feed_text' => $data['ftext'],
                    'referer' => $referer);
                Mailing::sendLetterCommon($this->globalsettings['mail_feedback'], 4, $mail_attrs);
                unset($_POST);
                unset($_SESSION['captcha_keystring']);
                $this->content = $this->getCommonSuccess($data);
            } else {
                $this->content = $this->getCommonForm($data);
            }

            unset($_SESSION['captcha_keystring']);
        } else {
            $this->content = $this->getCommonForm($data);
        }
    }

    private function getCommonForm($data) {
        foreach ($data as $k => $v) {
            $this->smarty->assign($k, $v);
        }
        return $this->smarty->fetch(_DIR_TEMPLATES . '/feedback/feedpage.sm.html');
    }

    private function getCommonSuccess($data) {
        foreach ($data as $k => $v) {
            $this->smarty->assign($k, $v);
        }
        return $this->smarty->fetch(_DIR_TEMPLATES . '/feedback/feedsuccess.sm.html');
    }

    private function getAddingForm() {
        //
    }

    private function getCaptcha() {
        include(_DIR_ADDONS . '/kcaptcha/kcaptcha.php');
        $captcha = new KCAPTCHA();
        $_SESSION['captcha_keystring'] = $captcha->getKeyString();
        exit();
    }

    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
