<?php

use app\core\SiteRequest;
use app\db\MyDB;

class Page extends Core
{
    /**
     * @inheritDoc
     */
    public function compileContent(): void
    {
        $bg = new MBlogEntries($this->db);
        $ns = new MNewsItems($this->db);

        $blog = $bg->getLastWithTS($this->globalConfig->getIndexStatCountBlog());
        $blogEntries = $blog['blogentries'];

        $this->response->setLastEditTimestamp($blog['max_ts']);

        $news = $ns->getLastWithTS($this->globalConfig->getIndexStatCountNews());
        $newsEntries = $news['entries'];

        $this->response->setMaxLastEditTimestamp($news['max_ts']);

        $this->smarty->assign('hello_text', $this->response->getContent()->getBody());
        $this->smarty->assign('stat', $this->globalConfig->getIndexStatText());
        $this->smarty->assign('blogentries', $blogEntries);
        $this->smarty->assign('agrnewsentries', $newsEntries);

        $this->response->getContent()->setBody($this->smarty->fetch(_DIR_TEMPLATES . '/index.html/index.sm.html'));
    }
}
