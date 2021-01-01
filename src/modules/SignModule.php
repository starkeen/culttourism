<?php

declare(strict_types=1);

namespace app\modules;

use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;

class SignModule extends Module implements ModuleInterface
{
    private const COOKIE_KEY = 'userkey';

    /**
     * @inheritDoc
     * @throws RedirectException
     * @throws NotFoundException
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        if ($request->getLevel1() === 'in') {
            $response->getContent()->setBody($this->getIn());
        } elseif ($request->getLevel1() === 'up') {
            $response->getContent()->setBody($this->getUp());
        } elseif ($request->getLevel1() === 'check') {
            $this->doCheck($request->getLevel2());
        } elseif ($request->getLevel1() === 'out') {
            $this->doOut();
        } elseif ($request->getLevel1() === 'form') {
            $response->getContent()->setBody($this->getFormLogin());
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'sign';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }

    /**
     * Форма авторизации
     *
     * @return string
     */
    private function getIn(): string
    {
        $uniqueKey = $this->getRandomString();
        if (!isset($_SESSION[self::COOKIE_KEY]) || !$_SESSION[self::COOKIE_KEY]) {
            $_SESSION[self::COOKIE_KEY] = $uniqueKey;
        } else {
            $uniqueKey = $_SESSION[self::COOKIE_KEY];
        }

        return $this->templateEngine->getContent('sign/in.tpl', [
            'key' => $uniqueKey,
            'url' => GLOBAL_SITE_URL,
        ]);
    }

    /**
     * Форма регистрации
     * @return string
     */
    private function getUp(): string
    {
        return $this->templateEngine->getContent('sign/up.tpl');
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
            $returnUrl = $_SESSION['user_referer'] ?? GLOBAL_SITE_URL;
        } else {
            $returnUrl = '/sign/in/';
        }

        throw new RedirectException($returnUrl);
    }

    /**
     * @return string
     */
    private function getFormLogin(): string
    {
        if (isset($_SESSION['user_id'])) {
            $this->templateEngine->assign('username', $this->webUser->getName());
            return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/sign/authuser.tpl');
        }

        $this->templateEngine->assign('baseurl', GLOBAL_SITE_URL);
        $this->templateEngine->assign('authkey', 'ewtheqryb35yqb356y4ery');

        return $this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/sign/authform.tpl');
    }

    /**
     * @return string
     */
    private function getRandomString(): string
    {
        return hash('sha256', uniqid((string) random_int(0, PHP_INT_MAX), true));
    }
}
