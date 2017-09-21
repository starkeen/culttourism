<?php

use app\db\FactoryDB;

class Page extends PageCommon
{
    public function __construct($module_id, $page_id)
    {
        $db = FactoryDB::db();
        parent::__construct($db, 'index.html', $page_id);

        $bg = new MBlogEntries($this->db);
        $ns = new MNewsItems($this->db);

        $blog = $bg->getLastWithTS($this->globalsettings['index_cnt_blogs']);
        $blogentries = $blog['blogentries'];
        if ($blog['max_ts'] > $this->lastedit_timestamp) {
            $this->lastedit_timestamp = $blog['max_ts'];
        }

        $news = $ns->getLastWithTS($this->globalsettings['index_cnt_news']);
        $newsentries = $news['entries'];
        if ($news['max_ts'] > $this->lastedit_timestamp) {
            $this->lastedit_timestamp = $news['max_ts'];
        }

        $this->smarty->assign('hello_text', $this->content);
        $this->smarty->assign('stat', $this->globalsettings['stat_text']);
        $this->smarty->assign('blogentries', $blogentries);
        $this->smarty->assign('agrnewsentries', $newsentries);

        $this->content = $this->smarty->fetch(_DIR_TEMPLATES . '/index.html/index.sm.html');
    }

    public static function getInstance($db, $mod)
    {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
