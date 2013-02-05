<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        global $smarty;
        parent::__construct($db, 'map', $page_id);
        $id = urldecode($id);
        if (strpos($id, '?') !== FALSE)
            $id = substr($id, 0, strpos($id, '?'));
        $this->id = $id;

        //========================  I N D E X  ================================
        if ($page_id == '') {
            $this->ymaps_ver = 2;
            //$smarty->assign('gps_text', $this->content);
            $this->content = $smarty->fetch(_DIR_TEMPLATES . '/map/map.sm.html');
            return true;
        }
        //==========================  E X I T  ================================
        else
            $this->getError('404');
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}

?>
