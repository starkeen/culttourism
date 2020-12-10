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
            $this->prepareIndex();
        } //========================   L I S T   ================================
        elseif (preg_match('/([a-z0-9_-]+)\.html/i', $url_last, $regs)) {
            $this->prepareListBySlug($regs[1]);
        }
    }

    private function prepareListBySlug(string $slug): void
    {
        $lst = new MLists($this->db);
        $list = $lst->getItemBySlugLine($slug);
        if (isset($list['ls_id']) && $list['ls_id'] > 0) {
            $this->h1 = $list['ls_title'];
            $this->addDescription($list['ls_description']);
            $this->addKeywords($list['ls_keywords']);
            $this->addTitle($list['ls_title']);
            $this->addOGMeta(OgType::TITLE(), $list['ls_title']);
            $this->addOGMeta(OgType::DESCRIPTION(), $list['ls_description']);
            if (!empty($list['ls_image'])) {
                $objImage = $this->getAbsoluteURL($list['ls_image']);
                $this->addOGMeta(OgType::IMAGE(), $objImage);
            }
            $this->canonical = '/list/' . $slug . '.html';
            $this->addOGMeta(OgType::URL(), rtrim(_SITE_URL, '/') . $this->canonical);

            $this->lastedit_timestamp = $list['last_update'];

            $listItems = new MListsItems($this->db, $list['ls_id']);

            $this->smarty->assign('list', $list);
            $this->smarty->assign('list_items', $listItems->getActive());

            $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/list/list.sm.html');
        } else {
            $this->processError(Core::HTTP_CODE_404);
        }
    }

    private function prepareIndex(): void
    {
        $this->canonical = '/list/';
        $this->addOGMeta(OgType::URL(), rtrim(_SITE_URL, '/') . $this->canonical);

        $lst = new MLists($this->db);

        $indexLists = [];
        foreach ($lst->getActive() as $list) {
            if ($list['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $list['last_update'];
            }
            $indexLists[] = $list;
        }

        $this->smarty->assign('index_text', $this->content);
        $this->smarty->assign('index_lists', $indexLists);
        $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/list/index.sm.html');
    }

    public static function getInstance($db, $mod)
    {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }
}
