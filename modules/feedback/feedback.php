<?php

use app\ReCaptcha;
use exceptions\SpamException;
use GuzzleHttp\Client;

class Page extends PageCommon
{

    public function __construct($db, $mod)
    {
        list($module_id, $page_id, $id) = $mod;
        parent::__construct($db, 'feedback', $page_id);

        if ((string) $page_id === '') {
            $this->getCommon();
        } elseif ($page_id === 'getcapt') {
            $this->getCaptcha();
        } elseif ($page_id === 'newpoint') {
            $this->getAdd();
        } else {
            $this->getError('404');
        }
    }

    private function getAdd()
    {
        $cp = new MCandidatePoints($this->db);
        if (!isset($_SESSION['feedback_referer']) || $_SESSION['feedback_referer'] == null) {
            $_SESSION['feedback_referer'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        }
        if (isset($_POST) && !empty($_POST)) {
            $httpClient = new Client();
            $reCaptcha = new ReCaptcha($httpClient);
            $spamStatusOK = $reCaptcha->check($_POST['g-recaptcha-response'] ?? null);

            $cp->add(
                [
                    'cp_title' => $_POST['title'],
                    'cp_city' => $_POST['region'],
                    'cp_text' => $_POST['descr'],
                    'cp_addr' => $_POST['addrs'] ?? '',
                    'cp_phone' => $_POST['phone'],
                    'cp_web' => $_POST['web'],
                    'cp_worktime' => $_POST['worktime'],
                    'cp_referer' => $_SESSION['feedback_referer'],
                    'cp_sender' => $_POST['name'] . ' <' . $_POST['email'] . '>',
                    'cp_source_id' => MCandidatePoints::SOURCE_FORM,
                    'cp_state' => $spamStatusOK === true ? MCandidatePoints::STATUS_NEW : MCandidatePoints::STATUS_SPAM,
                    'cp_active' => $spamStatusOK === true ? 1 : 0,
                ]
            );

            if ($spamStatusOK === true) {
                $mail_attrs = [
                    'user_name' => $_POST['name'],
                    'user_mail' => $_POST['email'],
                    'add_city' => $_POST['region'],
                    'add_title' => $_POST['title'],
                    'add_text' => $_POST['descr'],
                    'add_contacts' => $_POST['addrs']
                        . ' ' . $_POST['phone']
                        . ' ' . $_POST['web']
                        . ' ' . $_POST['worktime'],
                    'referer' => $_SESSION['feedback_referer']
                ];

                Mailing::sendLetterCommon($this->globalsettings['mail_feedback'], 5, $mail_attrs);
                unset($_SESSION['feedback_referer'], $_SESSION['captcha_keystring']);
            }

            $this->content = $this->getAddingSuccess($_POST['title'], $_POST['descr'], $_POST['region']);
            unset($_POST);
        } else {
            $this->content = $this->getAddingForm();
        }
    }

    private function getCommon()
    {
        $data = [
            'error' => null,
            'success' => null,
            'fname' => null,
            'ftext' => null,
            'fmail' => null,
        ];
        if ((!isset($_SESSION['feedback_referer']) || $_SESSION['feedback_referer'] == null) && isset($_SERVER['HTTP_REFERER'])) {
            $_SESSION['feedback_referer'] = $_SERVER['HTTP_REFERER'];
        }
        $referer = !empty($_SESSION['feedback_referer']) ? $_SESSION['feedback_referer'] : null;
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
                $fb->add(
                    [
                        'fb_name' => $data['fname'],
                        'fb_text' => $data['ftext'],
                        'fb_sendermail' => $data['fmail'],
                        'fb_referer' => $referer,
                        'fb_ip' => $_SERVER['REMOTE_ADDR'],
                        'fb_browser' => $_SERVER['HTTP_USER_AGENT'],
                    ]
                );
                $mail_attrs = [
                    'user_name' => $data['fname'],
                    'user_mail' => $data['fmail'],
                    'feed_text' => $data['ftext'],
                    'referer' => $referer,
                ];
                Mailing::sendLetterCommon($this->globalsettings['mail_feedback'], 4, $mail_attrs);
                unset($_POST);
                unset($_SESSION['captcha_keystring']);
                unset($_SESSION['feedback_referer']);
                $this->content = $this->getCommonSuccess($data);
            } else {
                $this->content = $this->getCommonForm($data);
            }

            unset($_SESSION['captcha_keystring']);
        } else {
            $this->content = $this->getCommonForm($data);
        }
    }

    private function getCommonForm($data)
    {
        foreach ($data as $k => $v) {
            $this->smarty->assign($k, $v);
        }
        return $this->smarty->fetch(_DIR_TEMPLATES . '/feedback/feedpage.sm.html');
    }

    private function getCommonSuccess($data)
    {
        foreach ($data as $k => $v) {
            $this->smarty->assign($k, $v);
        }
        return $this->smarty->fetch(_DIR_TEMPLATES . '/feedback/feedsuccess.sm.html');
    }

    /**
     * @return string
     */
    private function getAddingForm()
    {
        $this->addTitle('Добавить объект (музей, гостиницу, кафе и др.)');
        $this->smarty->assign('recaptcha_key', ReCaptcha::KEY);
        return $this->smarty->fetch(_DIR_TEMPLATES . '/feedback/addpoint.sm.html');
    }

    private function getAddingSuccess($title, $descr, $region)
    {
        $this->smarty->assign('add_title', $title);
        $this->smarty->assign('add_descr', nl2br($descr));
        $this->smarty->assign('add_region', $region);
        return $this->smarty->fetch(_DIR_TEMPLATES . '/feedback/addsuccess.sm.html');
    }

    private function getCaptcha()
    {
        include(_DIR_ADDONS . '/kcaptcha/kcaptcha.php');
        $captcha = new KCAPTCHA();
        $_SESSION['captcha_keystring'] = $captcha->getKeyString();
        exit();
    }

    public static function getInstance($db, $mod = null)
    {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
