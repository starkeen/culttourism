<?php

use app\constant\OgType;
use app\core\SiteRequest;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\utils\Urls;

class Page extends Core
{
    /**
     * @inheritDoc
     */
    public function compileContent(): void
    {
        $id = urldecode($this->siteRequest->getLevel2());
        if (strpos($id, '?') !== false) {
            $id = substr($id, 0, strpos($id, '?'));
        }
        $this->id = $id;
        $regs = [];

        $this->response->getContent()->setCustomJsModule($this->siteRequest->getModuleKey());

        $url_array = explode('/', $this->siteRequest->getLevel1());
        $url_last = array_pop($url_array);

        //========================  I N D E X  ================================
        if ($this->siteRequest->getLevel1() === null) {
            $this->prepareIndex();
        } //========================   L I S T   ================================
        elseif (preg_match('/([a-z0-9_-]+)\.html/i', $url_last, $regs)) {
            $this->prepareListBySlug($regs[1]);
        }
    }

    /**
     * @param string $slug
     * @throws NotFoundException
     */
    private function prepareListBySlug(string $slug): void
    {
        $lst = new MLists($this->db);
        $list = $lst->getItemBySlugLine($slug);
        if (isset($list['ls_id']) && $list['ls_id'] > 0) {
            $this->response->getContent()->setH1($list['ls_title']);
            $this->response->getContent()->getHead()->addDescription($list['ls_description']);
            $this->response->getContent()->getHead()->addKeyword($list['ls_keywords']);
            $this->response->getContent()->getHead()->addTitleElement($list['ls_title']);
            $this->response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $list['ls_title']);
            $this->response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $list['ls_description']);
            if (!empty($list['ls_image'])) {
                $objImage = Urls::getAbsoluteURL($list['ls_image']);
                $this->response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $objImage);
            }
            $this->response->getContent()->getHead()->setCanonicalUrl('/list/' . $slug . '.html');
            $this->response->getContent()->getHead()->addOGMeta(OgType::URL(), $this->response->getContent()->getHead()->getCanonicalUrl());

            $this->response->setLastEditTimestamp($list['last_update']);

            $listItems = new MListsItems($this->db, $list['ls_id']);

            $this->smarty->assign('list', $list);
            $this->smarty->assign('list_items', $listItems->getActive());

            $this->response->getContent()->setBody($this->smarty->fetch(_DIR_TEMPLATES . '/list/list.sm.html'));
        } else {
            throw new NotFoundException();
        }
    }

    private function prepareIndex(): void
    {
        $this->response->getContent()->getHead()->setCanonicalUrl('/list/');
        $this->response->getContent()->getHead()->addOGMeta(OgType::URL(), $this->response->getContent()->getHead()->getCanonicalUrl());

        $lst = new MLists($this->db);

        $indexLists = [];
        foreach ($lst->getActive() as $list) {
            $this->response->setMaxLastEditTimestamp($list['last_update']);
            $indexLists[] = $list;
        }

        $this->smarty->assign('index_text', $this->response->getContent()->getBody());
        $this->smarty->assign('index_lists', $indexLists);
        $this->response->getContent()->setBody($this->smarty->fetch(_DIR_TEMPLATES . '/list/index.sm.html'));
    }
}
