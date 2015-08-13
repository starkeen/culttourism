<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        @list($module_id, $page_id, $id, $id2) = $mod;
        global $smarty;
        parent::__construct($db, 'blog');
        $this->id = $id;
        if ($page_id == '') {
            $this->content = $this->getAllEntries($smarty, $db); //все записи
        } elseif ($id2 != '') {
            $this->content = $this->getOneEntry($smarty, $db, $id2, $page_id, $id); //одна запись
        } elseif ($page_id != '') {
            $this->content = $this->getCalendar($smarty, $db, $page_id, $id); //календарь
        } else {
            $this->getError('404');
        }
    }

    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

    private function getAllEntries($sm, $db) {
        $dbb = $db->getTableName('blogentries');
        $dbu = $db->getTableName('users');
        $show_full_admin = $this->checkEdit();
        $show_full_sql = "";
        if (!$show_full_admin) {
            $show_full_sql = "HAVING br_showed = 1\n";
        }
        $db->sql = "SELECT bg.*, us.us_name,
                            UNIX_TIMESTAMP(bg.br_date) AS last_update,
                            IF(bg.br_date < now(),1,0) as br_showed,
                            DATE_FORMAT(bg.br_date,'%Y') as bg_year, DATE_FORMAT(bg.br_date,'%m') as bg_month, 
                            DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                            IF (bg.br_url != '', bg.br_url, DATE_FORMAT(bg.br_date,'%d')) as bg_day,
                            IF (bg.br_url != '', CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'), CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')) as br_link
                    FROM $dbb bg
                    LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                    $show_full_sql
                    ORDER BY bg.br_date DESC
                    LIMIT 20";
        //$db->showSQL();
        $db->exec();
        $entry = array();
        while ($row = $db->fetch()) {
            $entry[$row['br_id']] = $row;
            if ($row['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $row['last_update'];
            }
        }
        if ($show_full_admin) {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
        }
        $sm->assign('entries', $entry);
        $sm->assign('blogadmin', $show_full_admin);
        return $sm->fetch(_DIR_TEMPLATES . '/blog/blog.all.sm.html');
    }

    private function getOneEntry($sm, $db, $id, $y = null, $m = null) {
        $id = urldecode($id);
        $id = substr($id, 0, strpos($id, '.html'));
        $year = intval($y);
        $month = intval($m);
        $idn = intval($id);

        $entry = NULL;

        if ($bid = $this->checkDate($db, $year, $month, $idn)) {
            $entry = $this->getEntryByID($db, $bid);
        } elseif ($bid = $this->checkURL($db, $id)) {
            $entry = $this->getEntryByID($db, $bid);
        } else {
            $this->getError('404');
        }
        $sm->assign('entry', $entry);
        return $sm->fetch(_DIR_TEMPLATES . '/blog/blog.one.sm.html');
    }

    private function getCalendar($sm, $db, $y, $m = null) {
        $year = intval($y);
        $month = intval($m);
        if ($year) {
            $this->addTitle($year);
            $this->addKeywords('год ' . $year);
            $this->addDescription("Записи в блоге за $year год");
        }
        if ($month) {
            $this->addTitle($m);
            $this->addKeywords('месяц ' . $month);
            $this->addDescription("Записи в блоге за $month месяц");
        }
        $dbb = $db->getTableName('blogentries');
        $dbu = $db->getTableName('users');
        $binds = array(
            ':year' => $year,
        );
        $db->sql = "SELECT bg.br_id, bg.br_title, bg.br_text, us.us_name,
                        UNIX_TIMESTAMP(bg.br_date) AS last_update,
                        DATE_FORMAT(bg.br_date,'%Y') as bg_year, DATE_FORMAT(bg.br_date,'%m') as bg_month, 
                        IF (bg.br_url != '', bg.br_url, DATE_FORMAT(bg.br_date,'%d')) as bg_day,
                        IF (bg.br_url != '', CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'), CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')) as br_link,
                        DATE_FORMAT(bg.br_date,'%d.%m.%Y') as br_datex
                    FROM $dbb as bg
                        LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                    WHERE br_active = 1
                        AND DATE_FORMAT(br_date, '%Y') = :year\n";
        if ($month) {
            $db->sql .= "AND DATE_FORMAT(br_date, '%c') = :month\n";
            $binds[':month'] = $month;
        }
        $db->sql .= "AND br_date < NOW()
                    ORDER BY bg.br_date DESC";
        $db->execute($binds);
        $entry = array();
        while ($row = $db->fetch()) {
            $entry[$row['bg_month']][$row['br_id']] = $row;
            if ($row['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $row['last_update'];
            }
        }
        $sm->assign('entries', $entry);

        $db->sql = "SELECT DISTINCT DATE_FORMAT(bg.br_date,'%Y') as bg_year FROM $dbb AS bg ORDER BY bg_year";
        $db->exec();
        while ($row = $db->fetch()) {
            $years[] = $row['bg_year'];
        }
        $sm->assign('years', $years);
        $sm->assign('cur_year', $year);

        return $sm->fetch(_DIR_TEMPLATES . '/blog/blog.calendar.sm.html');
    }

    private function checkURL($db, $url) {
        $dbb = $db->getTableName('blogentries');
        $db->sql = "SELECT br_id FROM $dbb WHERE br_url = :url AND br_active = 1 LIMIT 1";
        $res = $db->execute(array(
            ':url' => $url,
        ));
        if ($res) {
            $row = $db->fetch();
            $bid = intval($row['br_id']);
            if (!$bid) {
                return false;
            }
            return $bid;
        } else {
            return FALSE;
        }
    }

    private function checkDate($db, $y, $m, $d) {
        $dbb = $db->getTableName('blogentries');
        $db->sql = "SELECT br_id FROM $dbb WHERE DATE_FORMAT(br_date, '%Y-%c-%e') = :date AND br_active = 1 LIMIT 1";
        $res = $db->execute(array(
            ':date' => "$y-$m-$d",
        ));
        if ($res) {
            $row = $db->fetch();
            $bid = intval($row['br_id']);
            if (!$bid) {
                return false;
            }
            return $bid;
        } else {
            return FALSE;
        }
    }

    private function getEntryByID($db, $id) {
        $dbb = $db->getTableName('blogentries');
        $dbu = $db->getTableName('users');
        $db->sql = "SELECT bg.*, us.us_name,
                        UNIX_TIMESTAMP(bg.br_date) AS last_update,
                        DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                        DATE_FORMAT(bg.br_date,'%Y') as bg_year, DATE_FORMAT(bg.br_date,'%m') as bg_month
                    FROM $dbb bg
                        LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                    WHERE br_active = 1
                        AND br_date < now()
                        AND br_id = :id
                    LIMIT 1";
        //$db->showSQL();
        $res = $db->execute(array(
            ':id' => intval($id),
        ));
        if (!$res) {
            return FALSE;
        }
        $out = $db->fetch();
        $this->addTitle($out['br_title']);
        $this->addDescription($out['br_title']);
        $this->addKeywords($out['br_title']);
        $this->addKeywords($out['br_url']);
        $this->addKeywords('месяц ' . $out['bg_month']);
        $this->addKeywords($out['bg_year'] . ' год');
        $this->lastedit_timestamp = $out['last_update'];
        return $out;
    }

}
