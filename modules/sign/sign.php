<?php

use app\core\SiteRequest;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;

class Page extends Core
{
    /**
     * @inheritDoc
     * @throws RedirectException
     * @throws NotFoundException
     */
    public function compileContent(): void
    {
        if ($this->siteRequest->getLevel1() === 'in') {
            $this->response->getContent()->setBody($this->getIn());
        } elseif ($this->siteRequest->getLevel1() === 'up') {
            $this->response->getContent()->setBody($this->getUp());
        } elseif ($this->siteRequest->getLevel1() === 'check') {
            $this->doCheck($this->siteRequest->getLevel2());
        } elseif ($this->siteRequest->getLevel1() === 'out') {
            $this->doOut();
        } elseif ($this->siteRequest->getLevel1() === 'form') {
            $this->response->getContent()->setBody($this->getFormLogin());
        } else {
            throw new NotFoundException();
        }
    }

    private function getIn()
    {
        $uniq_key = md5(uniqid(mt_rand(), true));
        if (!isset($_SESSION['userkey']) || !$_SESSION['userkey']) {
            $_SESSION['userkey'] = $uniq_key;
        } else {
            $uniq_key = $_SESSION['userkey'];
        }
        $this->templateEngine->assign('key', $uniq_key);
        $this->templateEngine->assign('url', _SITE_URL);
        return $this->templateEngine->fetch(_DIR_TEMPLATES . '/sign/in.sm.html');
    }

    private function getUp()
    {
        return $this->templateEngine->fetch(_DIR_TEMPLATES . '/sign/up.sm.html');
    }

    /**
     * @throws RedirectException
     */
    private function doOut(): void
    {
        $this->webUser->getAuth()->deleteKey();
        $_SESSION['user'] = null;
        $_SESSION['user_id'] = null;
        $_SESSION['user_name'] = null;
        $_SESSION['user_auth'] = null;

        $returnUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
        throw new RedirectException($returnUrl);
    }

    /**
     * @param string $key
     * @throws RedirectException
     */
    private function doCheck(string $key): void
    {
        if (isset($_SERVER['HTTP_REFERER']) && !isset($_SESSION['user_referer'])) {
            $_SESSION['user_referer'] = $_SERVER['HTTP_REFERER'];
        }
        if (!$key) {
            throw new RedirectException('/sign/in/');
        }
        if (!isset($_POST) || empty($_POST)) {
            throw new RedirectException('/sign/in/');
        }

        $email = trim($_POST['email']);
        $passw = trim($_POST['userpass']);

        if ($this->webUser->getAuth()->checkMailPassword($email, $passw)) {
            $returnUrl = $_SESSION['user_referer'] ?? _SITE_URL;
        } else {
            $returnUrl = '/sign/in/';
        }
        throw new RedirectException($returnUrl);
    }

    private function getFormLogin()
    {
        if (isset($_SESSION['user_id'])) {
            $this->templateEngine->assign('username', $this->webUser->getName());
            return $this->templateEngine->fetch(_DIR_TEMPLATES . '/sign/authuser.tpl');
        } else {
            $this->templateEngine->assign('baseurl', _SITE_URL);
            $this->templateEngine->assign('authkey', 'ewtheqryb35yqb356y4ery');
            return $this->templateEngine->fetch(_DIR_TEMPLATES . '/sign/authform.sm.html');
        }
    }
}
