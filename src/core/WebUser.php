<?php

declare(strict_types=1);

namespace app\core;

use Auth;

class WebUser
{
    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var SessionStorage
     */
    private $sessionStorage;

    /**
     * @param Auth           $auth
     * @param SessionStorage $sessionStorage
     */
    public function __construct(Auth $auth, SessionStorage $sessionStorage)
    {
        $this->auth = $auth;
        $this->sessionStorage = $sessionStorage;
    }

    /**
     * @return Auth
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }

    /**
     * @return bool
     */
    public function isGuest(): bool
    {
        return $this->getId() === null;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        if ($this->sessionStorage->getValue('user_id') !== null) {
            return (int) $this->sessionStorage->getValue('user_id');
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->sessionStorage->getValue('user_name');
    }

    /**
     * @return int|string
     */
    public function getHash()
    {
        return $this->getId() ?? session_id();
    }

    /**
     * проверяет возможность редактирования
     *
     * @return bool|null
     */
    public function isEditor(): ?bool
    {
        return $this->getId() !== null;
    }
}
