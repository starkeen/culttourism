<?php

/*
 * Class of common elements of modules
 */

class PageCommon extends Core {

    public $navibar = '';
    public $ymaps_ver = 1;

    public function __construct($db, $mod_id) {
        parent::__construct($db, $mod_id);
        global $smarty;

        $this->key_yandexmaps = $this->globalsettings['key_yandexmaps'];
        $this->mainfile_css = $this->globalsettings['mainfile_css'];
        $this->mainfile_js = $this->globalsettings['mainfile_js'];

        if (isset($_SESSION['user'])) {
            $this->user['object'] = $_SESSION['user'];
        }
        if (isset($_SESSION['user_name'])) {
            $this->user['username'] = $_SESSION['user_name'];
            $this->user['userid'] = $_SESSION['user_id'];
        }
    }

    public function checkEdit() {
        //проверяет возможность редактирования
        if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) != 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function getUserId() {
        if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) != 0) {
            return intval($_SESSION['user_id']);
        } else {
            return FALSE;
        }
    }

    public function getUserHash() {
        if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) != 0) {
            return intval($_SESSION['user_id']);
        } else {
            return session_id();
        }
    }

}

?>