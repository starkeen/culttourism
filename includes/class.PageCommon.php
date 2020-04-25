<?php

/*
 * Class of common elements of modules
 */

class PageCommon extends Core
{
    public $ymaps_ver = 1;

    /**
     * @var string
     */
    protected $key_yandexmaps;

    /**
     * @var string
     */
    public $mainfile_css;

    /**
     * @var string
     */
    public $mainfile_js;

    public function __construct($db, $mod_id) {
        parent::__construct($db, $mod_id);

        $this->key_yandexmaps = $this->globalsettings['key_yandexmaps'];
        $this->mainfile_css = $this->globalsettings['mainfile_css'];
        $this->mainfile_js = $this->globalsettings['mainfile_js'];
        
        $this->addOGMeta('url', rtrim(_SITE_URL, '/') . $_SERVER['REQUEST_URI']);
        $this->addOGMeta('title', $this->title);
        $this->addOGMeta('description', $this->description);

        if (isset($_SESSION['user'])) {
            $this->user['object'] = $_SESSION['user'];
        }
        if (isset($_SESSION['user_name'])) {
            $this->user['username'] = $_SESSION['user_name'];
            $this->user['userid'] = $_SESSION['user_id'];
        }
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
