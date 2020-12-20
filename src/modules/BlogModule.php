<?php

declare(strict_types=1);

namespace app\modules;

use app\constant\MonthName;
use app\constant\OgType;
use app\core\GlobalConfig;
use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;
use app\model\repository\BlogRepository;
use app\sys\TemplateEngine;
use app\utils\Dates;
use app\utils\Urls;
use MBlogEntries;

class BlogModule extends Module implements ModuleInterface
{
    private const MODULE_KEY = 'blog';

    /**
     * @var BlogRepository
     */
    private $blogRepository;

    /**
     * @param MyDB $db
     * @param TemplateEngine $templateEngine
     * @param WebUser $webUser
     * @param GlobalConfig $globalConfig
     */
    public function __construct(MyDB $db, TemplateEngine $templateEngine, WebUser $webUser, GlobalConfig $globalConfig)
    {
        parent::__construct($db, $templateEngine, $webUser, $globalConfig);

        $this->blogRepository = new BlogRepository($this->db);
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return self::MODULE_KEY;
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     * @throws RedirectException
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        if ($request->getLevel1() === null) {
            $this->fetchAllEntries($response); //все записи
        } elseif ($request->getLevel1() === 'addform') { //форма добавления записи в блог
            $response->setLastEditTimestampToFuture();
            $this->getFormBlog($response);
        } elseif ($request->getLevel1() === 'editform' && isset($_GET['brid']) && (int) $_GET['brid']) {
            $response->setLastEditTimestampToFuture();
            $this->getFormBlog($response, (int) $_GET['brid']);
        } elseif ($request->getLevel1() === 'saveform') {
            $response->setLastEditTimestampToFuture();
            $this->saveFormBlog();
        } elseif ($request->getLevel1() === 'delentry' && (int) $_GET['bid']) {
            $response->setLastEditTimestampToFuture();
            $this->deleteBlogEntry((int) $_GET['bid']);
        } elseif ($request->getLevel1() === 'blog') {
            throw new RedirectException('/blog/');
        } elseif ($request->getLevel3() !== null) { //одна запись
            $this->processOneEntry(
                $response,
                $request->getLevel3(),
                (int) $request->getLevel1(),
                (int) $request->getLevel2()
            );
        } elseif ($request->getLevel1() !== null) { //календарь
            $this->processCalendar(
                $response,
                (int) $request->getLevel1(),
                $request->getLevel2() !== null ? (int) $request->getLevel2() : null
            );
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === self::MODULE_KEY;
    }

    /**
     * Список последних постов по месяцам
     *
     * @param SiteResponse $response
     */
    private function fetchAllEntries(SiteResponse $response): void
    {
        $response->getContent()->getHead()->setCanonicalUrl('/blog/');

        $entries = $this->blogRepository->getLastEntries(20, $this->webUser->isEditor());
        if ($this->webUser->isEditor()) {
            $response->setLastEditTimestampToFuture();
        } else {
            foreach ($entries as $entry) {
                $response->setMaxLastEditTimestamp($entry->getTimestamp());
            }
        }

        $this->templateEngine->assign('entries', $entries);
        $this->templateEngine->assign('isAdmin', $this->webUser->isEditor());

        $body = $this->templateEngine->fetch(_DIR_TEMPLATES . '/blog/blog.all.tpl');

        $response->getContent()->setBody($body);
    }

    /**
     * @param SiteResponse $response
     * @param string $id
     * @param int $year
     * @param int $month
     * @throws NotFoundException
     */
    private function processOneEntry(SiteResponse $response, string $id, int $year, int $month): void
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
        $response->getContent()->getHead()->addTitleElement($entry['br_title']);
        $response->getContent()->getHead()->addDescription($entry['br_title']);
        $response->getContent()->getHead()->addKeyword($entry['br_title']);
        $response->getContent()->getHead()->addKeyword($entry['br_url']);
        $response->getContent()->getHead()->addKeyword('месяц ' . $entry['bg_month']);
        $response->getContent()->getHead()->addKeyword($entry['bg_year'] . ' год');
        $response->getContent()->getHead()->setCanonicalUrl($entry['br_canonical']);

        $response->setLastEditTimestamp($entry['last_update']);

        $response->getContent()->getHead()->addOGMeta(OgType::URL(), Urls::getAbsoluteURL($entry['br_canonical']));
        $response->getContent()->getHead()->addOGMeta(OgType::TYPE(), 'article');
        $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $entry['br_title']);
        $response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $entry['br_text']);
        if (!empty($entry['br_picture'])) {
            $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $entry['br_picture']);
        }

        $this->templateEngine->assign('entry', $entry);
        $contentBody = $this->templateEngine->fetch(_DIR_TEMPLATES . '/blog/blog.one.tpl');

        $response->getContent()->setBody($contentBody);
    }

    /**
     * @param SiteResponse $response
     * @param int $year
     * @param int|null $month
     */
    private function processCalendar(SiteResponse $response, int $year, int $month = null): void
    {
        $response->getContent()->getHead()->addTitleElement((string) $year);
        $response->getContent()->getHead()->addKeyword('год ' . $year);
        $response->getContent()->getHead()->addDescription('Записи в блоге за ' . $year . ' год');

        $canonical = '/blog/' . $year . '/';

        if ($month !== null) {
            $monthName = MonthName::getMonthName($month);
            $response->getContent()->getHead()->addTitleElement(mb_convert_case($monthName, MB_CASE_TITLE, 'UTF-8'));
            $response->getContent()->getHead()->addKeyword('месяц ' . $monthName);
            $response->getContent()->getHead()->addDescription("Записи в блоге за $monthName");
            $canonical .= sprintf('%02d', $month) . '/';
        }

        $response->getContent()->getHead()->setCanonicalUrl($canonical);

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
            $response->setLastEditTimestamp($row['last_update']);
        }
        $this->templateEngine->assign('entries', $entry);

        $this->db->sql = "SELECT DISTINCT DATE_FORMAT(bg.br_date,'%Y') as bg_year FROM $dbb AS bg ORDER BY bg_year";
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            $years[] = $row['bg_year'];
        }
        $this->templateEngine->assign('years', $years);
        $this->templateEngine->assign('cur_year', $year);

        $body = $this->templateEngine->fetch(_DIR_TEMPLATES . '/blog/blog.calendar.tpl');

        $response->getContent()->setBody($body);
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
     * @return null|int
     */
    private function checkDate($y, $m, $d): ?int
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
                return null;
            }

            return $bid;
        } else {
            return null;
        }
    }

    /**
     * @param int $id
     * @return array
     */
    private function getEntryByID(int $id): array
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
                ':id' => $id,
            ]
        );
        $out = $this->db->fetch();
        $out['br_canonical'] = '/blog/' . $out['bg_year'] . '/' . $out['bg_month'] . '/' . $out['br_url'] . '.html';

        return $out;
    }

    /**
     * @param int $bid
     */
    private function deleteBlogEntry(int $bid): void
    {
        if (!$this->webUser->isEditor()) {
            return;
        }
        $brid = (int) $_POST['brid'];
        if (!$brid || !$bid || $brid !== $bid) {
            return;
        }
        $bg = new MBlogEntries($this->db);
        $bg->deleteByPk($brid);
    }

    /**
     * @param SiteResponse $response
     * @param int|null $br_id
     */
    private function getFormBlog(SiteResponse $response, int $br_id = null): void
    {
        if (!$this->webUser->isEditor()) {
            return;
        }
        if ($br_id !== null) {
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
            $this->templateEngine->assign('entry', $entry);

            $body = $this->templateEngine->fetch(_DIR_TEMPLATES . '/blog/ajax.editform.sm.html');
        } else {
            $entry = [
                'br_day' => date('d.m.Y'),
                'br_time' => date('H:i'),
                'bg_year' => date('Y'),
                'bg_month' => date('m'),
                'br_url' => date('d'),
            ];
            $this->templateEngine->assign('entry', $entry);

            $body = $this->templateEngine->fetch(_DIR_TEMPLATES . '/blog/ajax.addform.sm.html');
        }

        $response->getContent()->setBody($body);
    }

    /**
     * @return bool|int|mixed
     * @throws NotFoundException
     */
    private function saveFormBlog(): void
    {
        if (!$this->webUser->isEditor()) {
            return;
        }

        $bg = new MBlogEntries($this->db);

        if ($_POST['brid'] === 'add') {
            $bg->insert(
                [
                    'br_title' => $_POST['ntitle'],
                    'br_text' => $_POST['ntext'],
                    'br_date' => Dates::normalToSQL($_POST['ndate']) . ' ' . $_POST['ntime'],
                    'br_active' => $_POST['nact'] === 'true' ? 1 : 0,
                    'br_url' => $_POST['nurl'],
                    'br_us_id' => $this->webUser->getId(),
                ]
            );
        } elseif ($_POST['brid'] > 0) {
            $bg->updateByPk(
                (int) $_POST['brid'],
                [
                    'br_title' => $_POST['ntitle'],
                    'br_text' => $_POST['ntext'],
                    'br_date' => Dates::normalToSQL($_POST['ndate']) . ' ' . $_POST['ntime'],
                    'br_active' => $_POST['nact'] === 'true' ? 1 : 0,
                    'br_url' => $_POST['nurl'],
                ]
            );
        } else {
            throw new NotFoundException();
        }
    }
}
