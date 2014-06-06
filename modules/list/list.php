<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        parent::__construct($db, 'list', $page_id);
        $id = urldecode($id);
        if (strpos($id, '?') !== FALSE) {
            $id = substr($id, 0, strpos($id, '?'));
        }
        $this->id = $id;
        $regs = array();
        //========================  I N D E X  ================================
        if ($page_id == '') {
            return $this->getIndex();
        }
        //========================   L I S T   ================================
        elseif (preg_match('/([a-z0-9_-]+)\.html/i', array_pop(explode('/', $page_id)), $regs)) {
            return $this->getListBySlug($regs[1]);
        }
    }

    private function getListBySlug($slug) {
        $lst = new Lists($this->db);
        $list = $lst->getItemBySlugline($slug);
        if (isset($list['data']['ls_id']) && $list['data']['ls_id'] > 0) {
            $this->h1 = $list['data']['ls_title'];
            $this->addDescription($list['data']['ls_description']);
            $this->addKeywords($list['data']['ls_keywords']);

            $lis = new ListsItems($this->db, $list['data']['ls_id']);

            $this->smarty->assign('about_text', $list['data']['ls_text']);
            $this->smarty->assign('list_items', $lis->getActive());

            $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/list/list.sm.html');
            return true;
        } else {
            $this->getError('404');
        }
    }

    private function getIndex() {
        $lst = new Lists($this->db);
        $this->smarty->assign('index_text', $this->content);
        $this->smarty->assign('index_lists', $lst->getActive());
        $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/list/index.sm.html');
        return true;
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
