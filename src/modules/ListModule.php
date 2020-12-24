<?php

declare(strict_types=1);

namespace app\modules;

use app\constant\OgType;
use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\exceptions\NotFoundException;
use app\utils\Urls;
use MLists;
use MListsItems;

class ListModule extends Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        $id = urldecode($request->getLevel2() ?? '');
        if (strpos($id, '?') !== false) {
            $id = substr($id, 0, strpos($id, '?'));
        }
        $this->id = $id;
        $regs = [];

        $response->getContent()->setCustomJsModule($request->getModuleKey());

        $url_array = explode('/', $request->getLevel1());
        $url_last = array_pop($url_array);

        //========================  I N D E X  ================================
        if ($request->getLevel1() === null) {
            $this->prepareIndex($response);
        } //========================   L I S T   ================================
        elseif (preg_match('/([a-z0-9_-]+)\.html/i', $url_last, $regs)) {
            $this->prepareListBySlug($regs[1], $response);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'list';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }

    /**
     * @param string $slug
     * @param SiteResponse $response
     * @throws NotFoundException
     */
    private function prepareListBySlug(string $slug, SiteResponse $response): void
    {
        $lst = new MLists($this->db);
        $list = $lst->getItemBySlugLine($slug);
        if (isset($list['ls_id']) && $list['ls_id'] > 0) {
            $response->getContent()->setH1($list['ls_title']);
            $response->getContent()->getHead()->addDescription($list['ls_description']);
            $response->getContent()->getHead()->addKeyword($list['ls_keywords']);
            $response->getContent()->getHead()->addTitleElement($list['ls_title']);
            $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $list['ls_title']);
            $response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $list['ls_description']);
            if (!empty($list['ls_image'])) {
                $objImage = Urls::getAbsoluteURL($list['ls_image']);
                $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $objImage);
            }
            $response->getContent()->getHead()->setCanonicalUrl('/list/' . $slug . '.html');
            $response->getContent()->getHead()->addOGMeta(OgType::URL(), $response->getContent()->getHead()->getCanonicalUrl());

            $response->setLastEditTimestamp($list['last_update']);

            $listItems = new MListsItems($this->db, $list['ls_id']);

            $this->templateEngine->assign('list', $list);
            $this->templateEngine->assign('list_items', $listItems->getActive());

            $response->getContent()->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/list/list.sm.html'));
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @param SiteResponse $response
     */
    private function prepareIndex(SiteResponse $response): void
    {
        $response->getContent()->getHead()->setCanonicalUrl('/list/');
        $response->getContent()->getHead()->addOGMeta(OgType::URL(), $response->getContent()->getHead()->getCanonicalUrl());

        $lst = new MLists($this->db);

        $indexLists = [];
        foreach ($lst->getActive() as $list) {
            $response->setMaxLastEditTimestamp($list['last_update']);
            $indexLists[] = $list;
        }

        $this->templateEngine->assign('index_text', $response->getContent()->getBody());
        $this->templateEngine->assign('index_lists', $indexLists);
        $response->getContent()->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/list/index.sm.html'));
    }
}
