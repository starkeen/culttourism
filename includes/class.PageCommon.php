<?php

use app\constant\OgType;
use app\core\SiteRequest;
use app\db\MyDB;

class PageCommon extends Core
{
    public $ymaps_ver = 1;

    /**
     * @var string
     */
    protected $key_yandexmaps;

    /**
     * @inheritDoc
     */
    protected function compileContent(): void
    {

    }

    /**
     * @return bool|null
     */
    public function checkEdit(): ?bool
    {
        //проверяет возможность редактирования
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== 0;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== 0) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    /**
     * @return int|string
     */
    public function getUserHash() {
        if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== 0) {
            return (int) $_SESSION['user_id'];
        }

        return session_id();
    }
}
