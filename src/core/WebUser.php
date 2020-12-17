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
     * @param Auth $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
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
        if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== 0) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    /**
     * @return int|string
     */
    public function getHash()
    {
        if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== 0) {
            return (int) $_SESSION['user_id'];
        }

        return session_id();
    }

    /**
     * @return bool|null
     */
    public function isEditor(): ?bool
    {
        //проверяет возможность редактирования
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== 0;
    }
}
