<?php
class Page extends PageCommon {
    public function __construct($module_id, $page_id) {
        global $db;
        global $smarty;

        parent::__construct($db, 'index.html', $page_id);

        $smarty->assign('hello_text', $this->content);
        $smarty->assign('stat', $this->globalsettings['stat_text']);

        $this->content = $smarty->fetch(_DIR_TEMPLATES.'/index.html/index.sm.html');
    }
    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }
}
?>
