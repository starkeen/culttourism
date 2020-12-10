<?php

use app\constant\OgType;

class Page extends PageCommon
{
    public function __construct($db, $mod)
    {
        [$module_id, $page_id, $id] = $mod;
        parent::__construct($db, 'list', $page_id);
        $id = urldecode($id);
        if (strpos($id, '?') !== false) {
            $id = substr($id, 0, strpos($id, '?'));
        }
        $this->id = $id;
        $regs = [];

        $this->mainfile_js = _ER_REPORT ? ('../sys/static/?type=js&pack=' . $module_id) : $this->globalsettings['res_js_' . $module_id];

        $url_array = explode('/', $page_id);
        $url_last = array_pop($url_array);

        //========================  I N D E X  ================================
        if ($page_id == '') {
            return $this->getIndex();
        } //========================   L I S T   ================================
        elseif (preg_match('/([a-z0-9_-]+)\.html/i', $url_last, $regs)) {
            return $this->getListBySlug($regs[1]);
        }
    }

    private function getListBySlug($slug)
    {
        $lst = new MLists($this->db);
        $list = $lst->getItemBySlugline($slug);
        if (isset($list['data']['ls_id']) && $list['data']['ls_id'] > 0) {
            $this->h1 = $list['data']['ls_title'];
            $this->addDescription($list['data']['ls_description']);
            $this->addKeywords($list['data']['ls_keywords']);
            $this->addTitle($list['data']['ls_title']);
            $this->addOGMeta(OgType::TITLE(), $list['data']['ls_title']);
            $this->addOGMeta(OgType::DESCRIPTION(), $list['data']['ls_description']);
            if (!empty($list['data']['ls_image'])) {
                $objImage = $this->getAbsoluteURL($list['data']['ls_image']);
                $this->addOGMeta(OgType::IMAGE(), $objImage);
            }

            $this->lastedit_timestamp = $list['data']['last_update'];

            $lis = new MListsItems($this->db, $list['data']['ls_id']);

            $this->smarty->assign('list', $list);
            $this->smarty->assign('list_items', $lis->getActive());

            $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/list/list.sm.html');
            return true;
        } else {
            $this->processError(Core::HTTP_CODE_404);
        }
    }

    private function getIndex()
    {
        $lst = new MLists($this->db);

        $index_list = [];
        foreach ($lst->getActive() as $list) {
            if ($list['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $list['last_update'];
            }
            $index_list[] = $list;
        }

        $this->smarty->assign('index_text', $this->content);
        $this->smarty->assign('index_lists', $index_list);
        $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/list/index.sm.html');

        return true;
    }

    public static function getInstance($db, $mod)
    {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }
}
