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

        $this->pageContent->setCustomJsModule($this->siteRequest->getModuleKey());

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
            $this->pageContent->setH1($list['ls_title']);
            $this->pageContent->getHead()->addDescription($list['ls_description']);
            $this->pageContent->getHead()->addKeyword($list['ls_keywords']);
            $this->pageContent->getHead()->addTitleElement($list['ls_title']);
            $this->pageContent->getHead()->addOGMeta(OgType::TITLE(), $list['ls_title']);
            $this->pageContent->getHead()->addOGMeta(OgType::DESCRIPTION(), $list['ls_description']);
            if (!empty($list['ls_image'])) {
                $objImage = Urls::getAbsoluteURL($list['ls_image']);
                $this->pageContent->getHead()->addOGMeta(OgType::IMAGE(), $objImage);
            }
            $this->pageContent->getHead()->setCanonicalUrl('/list/' . $slug . '.html');
            $this->pageContent->getHead()->addOGMeta(OgType::URL(), $this->pageContent->getHead()->getCanonicalUrl());

            $this->response->setLastEditTimestamp($list['last_update']);

            $listItems = new MListsItems($this->db, $list['ls_id']);

            $this->smarty->assign('list', $list);
            $this->smarty->assign('list_items', $listItems->getActive());

            $this->pageContent->setBody($this->smarty->fetch(_DIR_TEMPLATES . '/list/list.sm.html'));
        } else {
            throw new NotFoundException();
        }
    }

    private function prepareIndex(): void
    {
        $this->pageContent->getHead()->setCanonicalUrl('/list/');
        $this->pageContent->getHead()->pageContent->getHead()->addOGMeta(OgType::URL(), $this->pageContent->getHead()->getCanonicalUrl());

        $lst = new MLists($this->db);

        $indexLists = [];
        foreach ($lst->getActive() as $list) {
            if ($list['last_update'] > $this->response->getLastEditTimestamp()) {
                $this->response->setLastEditTimestamp($list['last_update']);
            }
            $indexLists[] = $list;
        }

        $this->smarty->assign('index_text', $this->pageContent->getBody());
        $this->smarty->assign('index_lists', $indexLists);
        $this->pageContent->setBody($this->smarty->fetch(_DIR_TEMPLATES . '/list/index.sm.html'));
    }
}
