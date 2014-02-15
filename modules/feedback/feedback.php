<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        if ($page_id == 'getcapt') {
            include(_DIR_ADDONS . '/kcaptcha/kcaptcha.php');
            $captcha = new KCAPTCHA();
            exit();
        }
        global $smarty;
        parent::__construct($db, 'feedback', $page_id);
        if (!isset($_SESSION['feedback_referer']) || $_SESSION['feedback_referer'] == null) {
            $_SESSION['feedback_referer'] = $_SERVER['HTTP_REFERER'];
        }
        $referer = $_SESSION['feedback_referer'];

        if (isset($_POST) && !empty($_POST)) {
            $fname = cut_trash_text($_POST['fname']);
            $fmail = cut_trash_text($_POST['fmail']);
            $ftext = cut_trash_text($_POST['ftext']);
            $fcapt = $_POST['fcapt'];
            $ftextcheck = cut_trash_text($_POST['ftextcheck']);
            $error = null;


            if (isset($_SESSION['captcha_keystring']) && $fcapt != $_SESSION['captcha_keystring']) {
                $error = 'fcapt';
            }
            if ($ftextcheck != '') {
                $error = 'fcapt';
            }
            if ($ftext == null) {
                $error = 'ftext';
            }
            if ($fname == null) {
                $error = 'fname';
            }

            $smarty->assign('error', $error);
            $smarty->assign('fname', $fname);
            $smarty->assign('ftext', $ftext);
            $smarty->assign('fmail', $fmail);
            unset($_SESSION['captcha_keystring']);

            if (!$error) {
                $xfip = $_SERVER['REMOTE_ADDR'];
                $xbrowser = $_SERVER['HTTP_USER_AGENT'];

                $dbf = $db->getTableName('feedback');
                $xfname = mysql_real_escape_string($fname);
                $xftext = mysql_real_escape_string($ftext);
                $xfmail = mysql_real_escape_string($fmail);
                $xreferer = mysql_real_escape_string($referer);

                $db->sql = "INSERT INTO $dbf
                            SET fb_date=now(), fb_name = '$xfname', fb_text='$xftext',
                            fb_referer = '$xreferer',
                            fb_sendermail='$xfmail', fb_ip='$xfip', fb_browser='$xbrowser'";

                if ($db->exec()) {
                    $smarty->assign('messtext', $ftext);
                    $smarty->assign('messname', $fname);
                    $smarty->assign('messmail', $fmail);
                    $smarty->assign('messdate', date('d.m.Y H:i:s'));
                    $smarty->assign('messageip', $xfip);
                    $smarty->assign('url_root', _URL_ROOT);
                    $smarty->assign('referer', $referer);
                    $mailtext = $smarty->fetch(_DIR_TEMPLATES . '/feedback/mailtome.sm.html');

                    include(_DIR_INCLUDES . "/class.Mailing.php");
                    if (Mailing::sendImmediately($db, _FEEDBACK_MAIL, $mailtext, 'Новое сообщение с сайта ' . _URL_ROOT)) {
                        $_SESSION['feedback_referer'] = null;
                        unset($_SESSION['feedback_referer']);
                        header('Location: /');
                        exit();
                    } else {
                        header('Location: /feedback/');
                        exit();
                    }
                }
            }
        } else {
            $smarty->assign('error', NULL);
            $smarty->assign('fname', NULL);
            $smarty->assign('ftext', NULL);
            $smarty->assign('fmail', NULL);
        }
        $this->content = $smarty->fetch(_DIR_TEMPLATES . '/feedback/feedpage.sm.html');
    }

    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}

?>