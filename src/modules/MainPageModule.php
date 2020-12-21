<?php

declare(strict_types=1);

namespace app\modules;

use app\core\GlobalConfig;
use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\sys\TemplateEngine;
use MBlogEntries;
use MNewsItems;

class MainPageModule extends Module implements ModuleInterface
{
    /**
     * @param MyDB $db
     * @param TemplateEngine $templateEngine
     * @param WebUser $webUser
     * @param GlobalConfig $globalConfig
     */
    public function __construct(MyDB $db, TemplateEngine $templateEngine, WebUser $webUser, GlobalConfig $globalConfig)
    {
        parent::__construct($db, $templateEngine, $webUser, $globalConfig);
    }

    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        $bg = new MBlogEntries($this->db);
        $ns = new MNewsItems($this->db);

        $blog = $bg->getLastWithTS($this->globalConfig->getIndexStatCountBlog());
        $blogEntries = $blog['blogentries'];

        $response->setLastEditTimestamp($blog['max_ts']);

        $news = $ns->getLastWithTS($this->globalConfig->getIndexStatCountNews());
        $newsEntries = $news['entries'];

        $response->setMaxLastEditTimestamp($news['max_ts']);

        $response->getContent()->getHead()->setCanonicalUrl('/');

        $this->templateEngine->assign('hello_text', $response->getContent()->getBody());
        $this->templateEngine->assign('stat', $this->globalConfig->getIndexStatText());
        $this->templateEngine->assign('blogentries', $blogEntries);
        $this->templateEngine->assign('agrnewsentries', $newsEntries);

        $response->getContent()->setBody($this->templateEngine->fetch(_DIR_TEMPLATES . '/index.html/main.page.tpl'));
    }

    protected function getModuleKey(): string
    {
        return 'index.html';
    }

    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }
}
