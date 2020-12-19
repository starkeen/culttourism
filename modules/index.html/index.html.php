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
        $blogentries = $blog['blogentries'];
        if ($blog['max_ts'] > $this->response->getLastEditTimestamp()) {
            $this->response->setLastEditTimestamp($blog['max_ts']);
        }

        $news = $ns->getLastWithTS($this->globalConfig->getIndexStatCountNews());
        $newsentries = $news['entries'];
        if ($news['max_ts'] > $this->response->getLastEditTimestamp()) {
            $this->response->setLastEditTimestamp($news['max_ts']);
        }

        $this->smarty->assign('hello_text', $this->pageContent->getBody());
        $this->smarty->assign('stat', $this->globalConfig->getIndexStatText());
        $this->smarty->assign('blogentries', $blogentries);
        $this->smarty->assign('agrnewsentries', $newsentries);

        $this->pageContent->setBody($this->smarty->fetch(_DIR_TEMPLATES . '/index.html/index.sm.html'));
    }
}
