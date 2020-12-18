<?php

use app\constant\MonthName;
use app\constant\OgType;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;
use app\utils\Urls;

class Page extends Core
{
    /**
     * @inheritDoc
     * @throws RedirectException
     * @throws NotFoundException
     */
    public function compileContent(): void
    {
        $this->id = $this->siteRequest->getLevel2();

        if ($this->siteRequest->getLevel1() === null) {
            $this->pageContent->setBody($this->getAllEntries()); //все записи
        } elseif ($this->siteRequest->getLevel1() === 'addform') { //форма добавления записи в блог
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            $this->pageContent->setBody($this->getFormBlog());
        } elseif ($this->siteRequest->getLevel1() === 'editform' && isset($_GET['brid']) && (int) $_GET['brid']) {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            $this->pageContent->setBody($this->getFormBlog((int) $_GET['brid']));
        } elseif ($this->siteRequest->getLevel1() === 'saveform') {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            $this->pageContent->setBody($this->saveFormBlog());
        } elseif ($this->siteRequest->getLevel1() === 'delentry' && (int) $_GET['bid']) {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            $this->pageContent->setBody($this->deleteBlogEntry((int) $_GET['bid']));
        } elseif ($this->siteRequest->getLevel1() === 'blog') {
            throw new RedirectException('/blog/');
        } elseif ($this->siteRequest->getLevel3() !== null ) { //одна запись
            $this->pageContent->setBody(
                $this->getOneEntry(
                    $this->siteRequest->getLevel3(),
                    (int) $this->siteRequest->getLevel1(),
                    (int) $this->siteRequest->getLevel2()
                )
            );
        } elseif ($this->siteRequest->getLevel1() !== null) { //календарь
            $this->pageContent->setBody(
                $this->getCalendar(
                    (int) $this->siteRequest->getLevel1(),
                    $this->siteRequest->getLevel2() !== null ? (int) $this->siteRequest->getLevel2() : null
                )
            );
        } else {
            throw new NotFoundException();
        }
    }

    private function getAllEntries()
    {
        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');
        $show_full_admin = $this->webUser->isEditor();
        $show_full_sql = '';
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
        $entry = [];
        while ($row = $this->db->fetch()) {
            $entry[$row['br_id']] = $row;
            if ($row['last_update'] > $this->lastedit_timestamp) {
                $this->lastedit_timestamp = $row['last_update'];
            }
        }
        if ($show_full_admin) {
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
        }

        $this->pageContent->getHead()->setCanonicalUrl('/blog/');

        $this->smarty->assign('entries', $entry);
        $this->smarty->assign('blogadmin', $show_full_admin);

        return $this->smarty->fetch(_DIR_TEMPLATES . '/blog/blog.all.tpl');
    }

    /**
     * @param string $id
     * @param int    $year
     * @param int    $month
     * @return string
     * @throws NotFoundException
     */
    private function getOneEntry(string $id, int $year, int $month): string
    {
        $id = urldecode($id);
        $id = substr($id, 0, strpos($id, '.html'));
        $idn = (int) $id;

        $entry = null;

        if ($bid = $this->checkDate($year, $month, $idn)) {
            $entry = $this->getEntryByID($bid);
        } elseif ($bid = $this->checkURL($id)) {
            $entry = $this->getEntryByID($bid);
        } else {
            throw new NotFoundException();
        }
        if (empty($entry['br_title'])) {
            throw new NotFoundException();
        }
        $this->pageContent->getHead()->addTitleElement($entry['br_title']);
        $this->pageContent->getHead()->addDescription($entry['br_title']);
        $this->pageContent->getHead()->addKeyword($entry['br_title']);
        $this->pageContent->getHead()->addKeyword($entry['br_url']);
        $this->pageContent->getHead()->addKeyword('месяц ' . $entry['bg_month']);
        $this->pageContent->getHead()->addKeyword($entry['bg_year'] . ' год');
        $this->pageContent->getHead()->setCanonicalUrl($entry['br_canonical']);

        $this->lastedit_timestamp = $entry['last_update'];

        $this->pageContent->getHead()->addOGMeta(OgType::URL(), Urls::getAbsoluteURL($entry['br_canonical']));
        $this->pageContent->getHead()->addOGMeta(OgType::TYPE(), 'article');
        $this->pageContent->getHead()->addOGMeta(OgType::TITLE(), $entry['br_title']);
        $this->pageContent->getHead()->addOGMeta(OgType::DESCRIPTION(), $entry['br_text']);
        if (!empty($entry['br_picture'])) {
            $this->pageContent->getHead()->addOGMeta(OgType::IMAGE(), $entry['br_picture']);
        }

        $this->smarty->assign('entry', $entry);

        return $this->smarty->fetch(_DIR_TEMPLATES . '/blog/blog.one.tpl');
    }

    /**
     * @param int $year
     * @param int|null $month
     * @return string
     */
    private function getCalendar(int $year, int $month = null): string
    {
        $this->pageContent->getHead()->addTitleElement((string) $year);
        $this->pageContent->getHead()->addKeyword('год ' . $year);
        $this->pageContent->getHead()->addDescription('Записи в блоге за ' . $year . ' год');

        $canonical = '/blog/' . $year . '/';

        if ($month !== null) {
            $monthName = MonthName::getMonthName($month);
            $this->pageContent->getHead()->addTitleElement(mb_convert_case($monthName, MB_CASE_TITLE, 'UTF-8'));
            $this->pageContent->getHead()->addKeyword('месяц ' . $monthName);
            $this->pageContent->getHead()->addDescription("Записи в блоге за $monthName");
            $canonical .= sprintf('%02d', $month) . '/';
        }

        $this->pageContent->getHead()->setCanonicalUrl($canonical);

        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');
        $binds = [
            ':year' => $year,
        ];
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
        $entry = [];
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

        return $this->smarty->fetch(_DIR_TEMPLATES . '/blog/blog.calendar.tpl');
    }

    /**
     * @param string $url
     * @return null|int
     */
    private function checkURL(string $url): ?int
    {
        $dbb = $this->db->getTableName('blogentries');
        $this->db->sql = "SELECT br_id FROM $dbb WHERE br_url = :url AND br_active = 1 LIMIT 1";
        $res = $this->db->execute(
            [
                ':url' => $url,
            ]
        );
        if ($res) {
            $row = $this->db->fetch();
            $bid = (int) $row['br_id'];
            if (!$bid) {
                return null;
            }
            return $bid;
        } else {
            return null;
        }
    }

    /**
     * @param $y
     * @param $m
     * @param $d
     * @return false|int
     */
    private function checkDate($y, $m, $d)
    {
        $dbb = $this->db->getTableName('blogentries');
        $this->db->sql = "SELECT br_id FROM $dbb WHERE DATE_FORMAT(br_date, '%Y-%c-%e') = :date AND br_active = 1 LIMIT 1";
        $res = $this->db->execute(
            [
                ':date' => "$y-$m-$d",
            ]
        );
        if ($res) {
            $row = $this->db->fetch();
            $bid = (int) $row['br_id'];
            if (!$bid) {
                return false;
            }
            return $bid;
        } else {
            return false;
        }
    }

    /**
     * @param $id
     * @return false|mixed|null
     */
    private function getEntryByID($id)
    {
        $dbb = $this->db->getTableName('blogentries');
        $dbu = $this->db->getTableName('users');
        $this->db->sql = "SELECT bg.*, us.us_name,
                        UNIX_TIMESTAMP(bg.br_date) AS last_update,
                        DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                        DATE_FORMAT(bg.br_date,'%Y') as bg_year,
                        DATE_FORMAT(bg.br_date,'%m') as bg_month
                    FROM $dbb bg
                        LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                    WHERE br_active = 1
                        AND br_date < now()
                        AND br_id = :id
                    LIMIT 1";
        $res = $this->db->execute(
            [
                ':id' => intval($id),
            ]
        );
        if (!$res) {
            return false;
        }
        $out = $this->db->fetch();
        $out['br_canonical'] = '/blog/' . $out['bg_year'] . '/' . $out['bg_month'] . '/' . $out['br_url'] . '.html';
        return $out;
    }

    /**
     * @param $bid
     * @return false|PDOStatement
     */
    private function deleteBlogEntry($bid)
    {
        if (!$this->webUser->isEditor()) {
            return false;
        }
        $brid = cut_trash_int($_POST['brid']);
        if (!$brid || !$bid || $brid != $bid) {
            return false;
        }
        $bg = new MBlogEntries($this->db);
        return $bg->deleteByPk($brid);
    }

    /**
     * @param null $br_id
     * @return false|string
     * @throws SmartyException
     */
    private function getFormBlog($br_id = null)
    {
        if (!$this->webUser->webUser->isEditor()) {
            return false;
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
            $entry = [
                'br_day' => date('d.m.Y'),
                'br_time' => date('H:i'),
                'bg_year' => date('Y'),
                'bg_month' => date('m'),
                'br_url' => date('d')
            ];
            $this->smarty->assign('entry', $entry);
            $this->lastedit_timestamp = mktime(0, 0, 0, 1, 2, 2030);
            return $this->smarty->fetch(_DIR_TEMPLATES . '/blog/ajax.addform.sm.html');
        }
    }

    /**
     * @return bool|int|mixed
     * @throws NotFoundException
     */
    private function saveFormBlog()
    {
        if (!$this->webUser->isEditor()) {
            return false;
        }

        $bg = new MBlogEntries($this->db);

        if ($_POST['brid'] === 'add') {
            return $bg->insert(
                [
                    'br_title' => $_POST['ntitle'],
                    'br_text' => $_POST['ntext'],
                    'br_date' => transSQLdate($_POST['ndate']) . ' ' . $_POST['ntime'],
                    'br_active' => $_POST['nact'] === 'true' ? 1 : 0,
                    'br_url' => $_POST['nurl'],
                    'br_us_id' => $this->webUser->getId(),
                ]
            );
        } elseif ($_POST['brid'] > 0) {
            return $bg->updateByPk(
                (int) $_POST['brid'],
                [
                    'br_title' => $_POST['ntitle'],
                    'br_text' => $_POST['ntext'],
                    'br_date' => transSQLdate($_POST['ndate']) . ' ' . $_POST['ntime'],
                    'br_active' => $_POST['nact'] === 'true' ? 1 : 0,
                    'br_url' => $_POST['nurl'],
                ]
            );
        } else {
            throw new NotFoundException();
        }
    }

    public static function getInstance($db, $mod = null)
    {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }
}
