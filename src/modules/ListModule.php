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
     * @throws     NotFoundException
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        $id = urldecode($request->getLevel2() ?? '');
        if (strpos($id, '?') !== false) {
            $id = substr($id, 0, strpos($id, '?'));
        }
        $regs = [];

        $response->getContent()->setCustomJsModule($request->getModuleKey());

        $urlParts = explode('/', $request->getLevel1() ?? '');
        $urlLastPart = array_pop($urlParts);

        //========================  I N D E X  ================================
        if ($request->getLevel1() === null) {
            $this->prepareIndex($response);
        } elseif (preg_match('/([a-z0-9_-]+)\.html/i', $urlLastPart, $regs)) {
            //========================   L I S T   ================================
            $this->prepareListBySlug($regs[1], $response);
        } else {
            throw new NotFoundException();
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
     * @param  string       $slug
     * @param  SiteResponse $response
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
            $canonical = '/list/' . $slug . '.html';
            $response->getContent()->getHead()->setCanonicalUrl($canonical);

            $response->getContent()->getHead()->addBreadcrumb('Списки достопримечательностей', '/list/');
            $response->getContent()->getHead()->addBreadcrumb($list['ls_title'], $canonical);

            $response->setLastEditTimestamp($list['last_update']);

            $listItems = new MListsItems($this->db, $list['ls_id']);

            $this->templateEngine->assign('list', $list);
            $this->templateEngine->assign('list_items', $listItems->getActive());

            $response->getContent()->setBody($this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/list/list.tpl'));
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

        $response->getContent()->getHead()->addBreadcrumb('Списки достопримечательностей', '/list/');

        $lst = new MLists($this->db);

        $indexLists = [];
        foreach ($lst->getActive() as $list) {
            $response->setMaxLastEditTimestamp($list['last_update']);
            $list['image'] = $list['ph_src'] ?? $list['ls_image'];
            $indexLists[] = $list;
        }

        $this->templateEngine->assign('index_text', $response->getContent()->getBody());
        $this->templateEngine->assign('index_lists', $indexLists);
        $response->getContent()->setBody($this->templateEngine->fetch(GLOBAL_DIR_TEMPLATES . '/list/index.tpl'));
    }
}
