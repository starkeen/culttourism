<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        @list($module_id, $page_id, $id, $id2) = $mod;
        parent::__construct($db, 'blog');
        $this->id = $id;
        if ($page_id == '') {
            $this->content = $this->getAllEntries(); //все записи
        } elseif ($page_id == 'addform') { //форма добавления записи в блог
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            $this->content = $this->getFormBlog();
        } elseif ($page_id == 'editform' && intval($_GET['brid'])) {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            $this->content = $this->getFormBlog(intval($_GET['brid']));
        } elseif ($page_id == 'saveform') {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            $this->content = $this->saveFormBlog();
        } elseif ($page_id == 'delentry' && intval($_GET['bid'])) {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            $this->content = $this->deleteBlogEntry(intval($_GET['bid']));
        } elseif ($id2 != '') {
            $this->content = $this->getOneEntry($id2, $page_id, $id); //одна запись
        } elseif ($page_id != '') {
            $this->content = $this->getCalendar($page_id, $id); //календарь
        } else {
            $this->getError('404');
        }
    }

    private function getAllEntries() {
        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');
        $show_full_admin = $this->checkEdit();
        $show_full_sql = "";
        if (!$show_full_admin) {
            $show_full_sql = "HAVING br_showed = 1\n";
        }
        $this->db->sql = "SELECT bg.*, us.us_name,
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
        $this->db->exec();
        $entry = array();
        while ($row = $this->db->fetch()) {
            $entry[$row['br_id']] = $row;
            if ($row['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $row['last_update'];
            }
        }
        if ($show_full_admin) {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
        }
        $this->smarty->assign('entries', $entry);
        $this->smarty->assign('blogadmin', $show_full_admin);
        return $this->smarty->fetch(_DIR_TEMPLATES . '/blog/blog.all.sm.html');
    }

    private function getOneEntry($id, $y = null, $m = null) {
        $id = urldecode($id);
        $id = substr($id, 0, strpos($id, '.html'));
        $year = intval($y);
        $month = intval($m);
        $idn = intval($id);

        $entry = NULL;

        if ($bid = $this->checkDate($year, $month, $idn)) {
            $entry = $this->getEntryByID($bid);
        } elseif ($bid = $this->checkURL($id)) {
            $entry = $this->getEntryByID($bid);
        } else {
            $this->getError('404');
        }
        $this->smarty->assign('entry', $entry);
        return $this->smarty->fetch(_DIR_TEMPLATES . '/blog/blog.one.sm.html');
    }

    private function getCalendar($y, $m = null) {
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
        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');
        $binds = array(
            ':year' => $year,
        );
        $this->db->sql = "SELECT bg.br_id, bg.br_title, bg.br_text, us.us_name,
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
            $this->db->sql .= "AND DATE_FORMAT(br_date, '%c') = :month\n";
            $binds[':month'] = $month;
        }
        $this->db->sql .= "AND br_date < NOW()
                    ORDER BY bg.br_date DESC";
        $this->db->execute($binds);
        $entry = array();
        while ($row = $this->db->fetch()) {
            $entry[$row['bg_month']][$row['br_id']] = $row;
            if ($row['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $row['last_update'];
            }
        }
        $this->smarty->assign('entries', $entry);

        $this->db->sql = "SELECT DISTINCT DATE_FORMAT(bg.br_date,'%Y') as bg_year FROM $dbb AS bg ORDER BY bg_year";
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            $years[] = $row['bg_year'];
        }
        $this->smarty->assign('years', $years);
        $this->smarty->assign('cur_year', $year);

        return $this->smarty->fetch(_DIR_TEMPLATES . '/blog/blog.calendar.sm.html');
    }

    private function checkURL($url) {
        $dbb = $this->db->getTableName('blogentries');
        $this->db->sql = "SELECT br_id FROM $dbb WHERE br_url = :url AND br_active = 1 LIMIT 1";
        $res = $this->db->execute(array(
            ':url' => $url,
        ));
        if ($res) {
            $row = $this->db->fetch();
            $bid = intval($row['br_id']);
            if (!$bid) {
                return false;
            }
            return $bid;
        } else {
            return FALSE;
        }
    }

    private function checkDate($y, $m, $d) {
        $dbb = $this->db->getTableName('blogentries');
        $this->db->sql = "SELECT br_id FROM $dbb WHERE DATE_FORMAT(br_date, '%Y-%c-%e') = :date AND br_active = 1 LIMIT 1";
        $res = $this->db->execute(array(
            ':date' => "$y-$m-$d",
        ));
        if ($res) {
            $row = $this->db->fetch();
            $bid = intval($row['br_id']);
            if (!$bid) {
                return false;
            }
            return $bid;
        } else {
            return FALSE;
        }
    }

    private function getEntryByID($id) {
        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');
        $this->db->sql = "SELECT bg.*, us.us_name,
                        UNIX_TIMESTAMP(bg.br_date) AS last_update,
                        DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                        DATE_FORMAT(bg.br_date,'%Y') as bg_year, DATE_FORMAT(bg.br_date,'%m') as bg_month
                    FROM $dbb bg
                        LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                    WHERE br_active = 1
                        AND br_date < now()
                        AND br_id = :id
                    LIMIT 1";
        $res = $this->db->execute(array(
            ':id' => intval($id),
        ));
        if (!$res) {
            return FALSE;
        }
        $out = $this->db->fetch();
        $this->addTitle($out['br_title']);
        $this->addDescription($out['br_title']);
        $this->addKeywords($out['br_title']);
        $this->addKeywords($out['br_url']);
        $this->addKeywords('месяц ' . $out['bg_month']);
        $this->addKeywords($out['bg_year'] . ' год');
        $this->lastedit_timestamp = $out['last_update'];
        return $out;
    }

    private function deleteBlogEntry($bid) {
        if (!$this->checkEdit()) {
            return FALSE;
        }
        $brid = cut_trash_int($_POST['brid']);
        if (!$brid || !$bid || $brid != $bid) {
            return FALSE;
        }
        $bg = new MBlogEntries($this->db);
        return $bg->deleteByPk($brid);
    }

    private function getFormBlog($br_id = null) {
        if (!$this->checkEdit()) {
            return FALSE;
        }
        if ($br_id) {
            $dbb = $this->db->getTableName('blogentries');
            $this->db->sql = "SELECT br_id, br_date, br_title, br_text, br_active, br_url,
                        DATE_FORMAT(br_date, '%d.%m.%Y') as br_day,
                        DATE_FORMAT(br_date, '%H:%i') as br_time,
                        DATE_FORMAT(br_date,'%Y') as bg_year, DATE_FORMAT(br_date,'%m') as bg_month, DATE_FORMAT(br_date,'%d') as bg_day
                        FROM $dbb
                        WHERE br_id = '$br_id'
                        LIMIT 1";
            $this->db->exec();
            $entry = $this->db->fetch();
            $this->smarty->assign('entry', $entry);
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            return $this->smarty->fetch(_DIR_TEMPLATES . '/blog/ajax.editform.sm.html');
        } else {
            $entry = array(
                'br_day' => date('d.m.Y'), 'br_time' => date('H:i'),
                'bg_year' => date('Y'), 'bg_month' => date('m'), 'br_url' => date('d'));
            $this->smarty->assign('entry', $entry);
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            return $this->smarty->fetch(_DIR_TEMPLATES . '/blog/ajax.addform.sm.html');
        }
    }

    private function saveFormBlog() {
        if (!$this->checkEdit()) {
            return FALSE;
        }

        $bg = new MBlogEntries($this->db);

        if ($_POST['brid'] == 'add') {
            return $bg->insert(array(
                        'br_title' => $_POST['ntitle'],
                        'br_text' => $_POST['ntext'],
                        'br_date' => transSQLdate($_POST['ndate']) . ' ' . $_POST['ntime'],
                        'br_active' => $_POST['nact'] == 'true' ? 1 : 0,
                        'br_url' => $_POST['nurl'],
                        'br_us_id' => $this->getUserId(),
            ));
        } elseif ($_POST['brid'] > 0) {
            return $bg->updateByPk(intval($_POST['brid']), array(
                        'br_title' => $_POST['ntitle'],
                        'br_text' => $_POST['ntext'],
                        'br_date' => transSQLdate($_POST['ndate']) . ' ' . $_POST['ntime'],
                        'br_active' => $_POST['nact'] == 'true' ? 1 : 0,
                        'br_url' => $_POST['nurl'],
            ));
        } else {
            return $this->getError('404');
        }
    }

    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
