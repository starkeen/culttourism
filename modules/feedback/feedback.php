<?php

use app\core\SiteRequest;
use app\db\MyDB;
use app\includes\ReCaptcha;
use app\utils\MyKCaptcha;
use GuzzleHttp\Client;

class Page extends PageCommon
{
    /**
     * @inheritDoc
     */
    protected function compileContent(): void
    {
        if ($this->siteRequest->getLevel1() === null) {
            $this->getCommon();
        } elseif ($this->siteRequest->getLevel1() === 'getcapt') {
            $this->showCaptcha();
        } elseif ($this->siteRequest->getLevel1() === 'newpoint') {
            $this->getAdd();
        } else {
            $this->processError(Core::HTTP_CODE_404);
        }
    }

    /**
     * Обработка формы добавления точки
     */
    private function getAdd(): void
    {
        $cp = new MCandidatePoints($this->db);
        if (!isset($_SESSION['feedback_referer']) || $_SESSION['feedback_referer'] == null) {
            $_SESSION['feedback_referer'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        }
        if (isset($_POST) && !empty($_POST)) {
            $httpClient = new Client();
            $reCaptcha = new ReCaptcha($httpClient);
            $spamStatusOK = $reCaptcha->check($_POST['g-recaptcha-response'] ?? null);

            $loggedSender = $_SESSION['user_id'] ?? null;
            $isAdminSender = $loggedSender !== null && (int) $loggedSender !== 0;

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

            if ($spamStatusOK === true && $isAdminSender !== true) {
                $mailAttrs = [
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

                Mailing::sendLetterCommon($this->globalConfig->getMailFeedback(), 5, $mailAttrs);
                unset($_SESSION['feedback_referer'], $_SESSION[MyKCaptcha::SESSION_KEY]);
            }

            $this->pageContent->setBody($this->getAddingSuccess($_POST['title'], $_POST['descr'], $_POST['region']));
            unset($_POST);
        } else {
            $this->pageContent->setBody($this->getAddingForm());
        }
    }

    private function getCommon(): void
    {
        $data = [
            'error' => null,
            'success' => null,
            'fname' => null,
            'fsurname' => null,
            'ftext' => null,
            'fmail' => null,
        ];
        if ((!isset($_SESSION['feedback_referer']) || $_SESSION['feedback_referer'] == null) && isset($_SERVER['HTTP_REFERER'])) {
            $_SESSION['feedback_referer'] = $_SERVER['HTTP_REFERER'];
        }
        $referer = !empty($_SESSION['feedback_referer']) ? $_SESSION['feedback_referer'] : null;
        if (isset($_POST) && !empty($_POST)) {
            $data['fname'] = cut_trash_text($_POST['fname']);
            $data['fsurname'] = $_POST['fsurname'] ?? null;
            $data['fmail'] = cut_trash_text($_POST['fmail']);
            $data['ftext'] = cut_trash_text($_POST['ftext']);
            $fcapt = $_POST['fcapt'];
            $ftextcheck = cut_trash_text($_POST['ftextcheck']);

            if (isset($_SESSION[MyKCaptcha::SESSION_KEY]) && $fcapt != $_SESSION[MyKCaptcha::SESSION_KEY]) {
                $data['error'] = 'fcapt';
            }
            if ($data['fname'] === 'Сотруднк') {
                $data['error'] = 'fcapt';
            }
            if ($data['fsurname'] === null) { // скрытое поле не было отправлено вообще
                $data['error'] = 'fcapt';
            }
            if ($data['fsurname'] !== '') { // скрытое поле было отправлено непустым
                $data['error'] = 'fcapt';
            }
            if (strpos($data['ftext'], 'drive.google.com') !== false) {
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
                Mailing::sendLetterCommon($this->globalConfig->getMailFeedback(), 4, $mail_attrs);
                unset($_POST);
                unset($_SESSION[MyKCaptcha::SESSION_KEY]);
                unset($_SESSION[MyKCaptcha::SESSION_KEY]);
                $this->pageContent->setBody($this->getCommonSuccess($data));
            } else {
                $this->pageContent->setBody($this->getCommonForm($data));
            }

            unset($_SESSION[MyKCaptcha::SESSION_KEY]);
        } else {
            $this->pageContent->setBody($this->getCommonForm($data));
        }
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws SmartyException
     */
    private function getCommonForm(array $data): string
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
    private function getAddingForm(): string
    {
        $this->pageContent->getHead()->addTitleElement('Добавить объект (музей, гостиницу, кафе и др.)');
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

    private function showCaptcha(): void
    {
        $captcha = new MyKCaptcha();
        $_SESSION[MyKCaptcha::SESSION_KEY] = $captcha->getKeyString();
        $captcha->captcha();
        exit();
    }

    public static function getInstance(MyDB $db, SiteRequest $request): self
    {
        return self::getInstanceOf(__CLASS__, $db, $request);
    }
}
