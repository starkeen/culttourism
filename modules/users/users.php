<?php
class Page extends PageCommon {
    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        global $smarty;
        parent::__construct($db, 'users');
        if ($id) $this->getError('404');
        $this->id = $id;
        $user_id = intval($page_id);
        if ($page_id == 0) $this->content = $this->getAllUsers($smarty);
        else $this->content = $this->getOneUser($smarty, $page_id);
    }
    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }
    
    private function getAllUsers($smarty) {
        include(_DIR_INCLUDES.'/class.Users.php');
        $smarty->assign('list', Users::getAllUsers());
        return $smarty->fetch(_DIR_TEMPLATES.'/users/list.sm.html');
    }

    private function getOneUser($smarty, $id) {
        include(_DIR_INCLUDES.'/class.Users.php');
        $profile = Users::getUserProfile($id);
        if (!$profile) $this->getError('404');
        $smarty->assign('profile', $profile);
        //print_x($profile);
        return $smarty->fetch(_DIR_TEMPLATES.'/users/one_user.sm.html');
    }
}
?>