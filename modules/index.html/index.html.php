<?php

class Page extends PageCommon {

    public function __construct($module_id, $page_id) {
        global $db;
        global $smarty;

        parent::__construct($db, 'index.html', $page_id);

        $dbb = $db->getTableName('blogentries');
        $dbu = $db->getTableName('users');

        $db->sql = "SELECT bg.*, us.us_name,
                           UNIX_TIMESTAMP(bg.br_date) AS last_update,
                           DATE_FORMAT(bg.br_date,'%Y') as bg_year, DATE_FORMAT(bg.br_date,'%m') as bg_month,
                           DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                           IF (bg.br_url != '', bg.br_url, DATE_FORMAT(bg.br_date,'%d')) as bg_day,
                           IF (bg.br_url != '', CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'), CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')) as br_link
                    FROM $dbb bg
                        LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                    WHERE bg.br_date < now()
                    ORDER BY bg.br_date DESC
                    LIMIT 3";
        $db->exec();
        $blogentries = array();
        while ($row = $db->fetch()) {
            $blogentries[$row['br_id']] = $row;
            if ($row['last_update'] > $this->lastedit_timestamp)
                $this->lastedit_timestamp = $row['last_update'];
        }

        $smarty->assign('hello_text', $this->content);
        $smarty->assign('stat', $this->globalsettings['stat_text']);
        $smarty->assign('blogentries', $blogentries);

        $this->content = $smarty->fetch(_DIR_TEMPLATES . '/index.html/index.sm.html');
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}

?>
