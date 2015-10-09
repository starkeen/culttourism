<?php

class Page extends PageCommon {

    public function __construct($module_id, $page_id) {
        $db = FactoryDB::db();
        global $smarty;

        parent::__construct($db, 'index.html', $page_id);

        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');
        $dbns = $this->db->getTableName('news_sourses');
        $dbni = $this->db->getTableName('news_items');

        $this->db->sql = "SELECT bg.*, us.us_name,
                           UNIX_TIMESTAMP(bg.br_date) AS last_update,
                           DATE_FORMAT(bg.br_date,'%Y') as bg_year, DATE_FORMAT(bg.br_date,'%m') as bg_month,
                           DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                           IF (bg.br_url != '', bg.br_url, DATE_FORMAT(bg.br_date,'%d')) as bg_day,
                           IF (bg.br_url != '', CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'), CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')) as br_link
                    FROM $dbb bg
                        LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                    WHERE bg.br_date < NOW()
                    ORDER BY bg.br_date DESC
                    LIMIT :limit_blogs";
        $this->db->execute(array(
            ':limit_blogs' => intval($this->globalsettings['index_cnt_blogs']),
        ));
        $blogentries = array();
        $patern = "/(.*?)<\/p>/i";
        while ($row = $this->db->fetch()) {
            $matches = array();
            preg_match_all($patern, $row['br_text'], $matches);
            if (isset($matches[0][0])) {
                $row['br_text'] = strip_tags($matches[0][0], '<p><a>');
            }
            $blogentries[$row['br_id']] = $row;
            if ($row['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $row['last_update'];
            }
        }

        $this->db->sql = "SELECT *,
                        UNIX_TIMESTAMP(ni.ni_pubdate) AS last_update,
                        DATE_FORMAT(ni.ni_pubdate,'%d.%m.%Y') as datex
                    FROM $dbni ni
                        LEFT JOIN $dbns ns ON ns.ns_id = ni.ni_ns_id
                    WHERE ni.ni_active = 1
                    GROUP BY ni_title
                    ORDER BY ni_pubdate DESC
                    LIMIT :limit_news";
        $this->db->execute(array(
            ':limit_news' => intval($this->globalsettings['index_cnt_news']),
        ));
        $agrnewsentries = array();
        while ($row = $this->db->fetch()) {
            $row['ni_text'] = strip_tags(html_entity_decode($row['ni_text'], ENT_QUOTES));
            $row['ni_text'] = trim(mb_substr($row['ni_text'], 0, mb_strrpos(mb_substr($row['ni_text'], 0, 350, 'utf-8'), '.', 'utf-8'), 'utf-8'), '\,');
            $sourse_url = parse_url($row['ns_web']);
            $row['ns_host'] = $sourse_url['host'];
            $agrnewsentries[] = $row;
            if ($row['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $row['last_update'];
            }
        }

        $smarty->assign('hello_text', $this->content);
        $smarty->assign('stat', $this->globalsettings['stat_text']);
        $smarty->assign('blogentries', $blogentries);
        $smarty->assign('agrnewsentries', $agrnewsentries);

        $this->content = $smarty->fetch(_DIR_TEMPLATES . '/index.html/index.sm.html');
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
