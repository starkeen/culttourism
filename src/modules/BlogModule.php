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
use app\exceptions\AccessDeniedException;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;
use app\model\entity\BlogEntry;
use app\model\entity\User;
use app\model\repository\BlogRepository;
use app\sys\TemplateEngine;
use app\utils\Dates;
use app\utils\Urls;

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
     * @throws AccessDeniedException
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
            if (!$this->webUser->isEditor()) {
                throw new AccessDeniedException();
            }
            $response->setLastEditTimestampToFuture();
            $this->saveFormBlog();
        } elseif ($request->getLevel1() === 'delentry' && (int) $_GET['bid']) {
            $response->setLastEditTimestampToFuture();
            if (!$this->webUser->isEditor()) {
                throw new AccessDeniedException();
            }
            $blogEntryId = (int) $_GET['bid'];
            $idFromRequest = (int) $_POST['brid'];
            if ($idFromRequest === 0 || $blogEntryId === 0 || $idFromRequest !== $blogEntryId) {
                throw new NotFoundException();
            }
            $this->blogRepository->deleteItem($idFromRequest);
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
        $decodedId = urldecode($id);
        $decodedId = substr($decodedId, 0, strpos($decodedId, '.html'));

        $entry = $this->blogRepository->getItem($decodedId, $month, $year);
        if ($entry === null) {
            throw new NotFoundException();
        }
        if (empty($entry->br_title)) {
            throw new NotFoundException();
        }

        $response->getContent()->getHead()->addTitleElement($entry->br_title);
        $response->getContent()->getHead()->addDescription($entry->br_title);
        $response->getContent()->getHead()->addKeyword($entry->br_title);
        $response->getContent()->getHead()->addKeyword($entry->br_url);
        $response->getContent()->getHead()->addKeyword(MonthName::getMonthName($entry->getMonthNumber()));
        $response->getContent()->getHead()->addKeyword($entry->getYear() . ' год');
        $response->getContent()->getHead()->setCanonicalUrl($entry->getRelativeLink());

        $response->setLastEditTimestamp($entry->getTimestamp());

        $response->getContent()->getHead()->addOGMeta(OgType::URL(), Urls::getAbsoluteURL($entry->getRelativeLink()));
        $response->getContent()->getHead()->addOGMeta(OgType::TYPE(), 'article');
        $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $entry->br_title);
        $response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $entry->br_title);
        if (!empty($entry->br_picture)) {
            $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $entry->br_picture);
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

        $entries = $this->blogRepository->getCalendarItems($year, $month);
        $lastMonth = array_key_last($entries);
        foreach ($entries[$lastMonth] as $entry) {
            $response->setMaxLastEditTimestamp($entry->getTimestamp());
        }
        $this->templateEngine->assign('entries', $entries);
        $this->templateEngine->assign('years', $this->blogRepository->getYears());
        $this->templateEngine->assign('cur_year', $year);

        $body = $this->templateEngine->fetch(_DIR_TEMPLATES . '/blog/blog.calendar.tpl');

        $response->getContent()->setBody($body);
    }

    /**
     * @param SiteResponse $response
     * @param int|null $id
     * @throws AccessDeniedException
     */
    private function getFormBlog(SiteResponse $response, int $id = null): void
    {
        if (!$this->webUser->isEditor()) {
            throw new AccessDeniedException();
        }
        if ($id !== null) {
            $entry = $this->blogRepository->getItemByPk($id);
            $this->templateEngine->assign('entry', $entry);
            $body = $this->templateEngine->fetch(_DIR_TEMPLATES . '/blog/ajax.editform.tpl');
        } else {
            $entry = [
                'br_day' => date('d.m.Y'),
                'br_time' => date('H:i'),
                'bg_year' => date('Y'),
                'bg_month' => date('m'),
                'br_url' => date('d'),
            ];
            $this->templateEngine->assign('entry', $entry);
            $body = $this->templateEngine->fetch(_DIR_TEMPLATES . '/blog/ajax.addform.tpl');
        }

        $response->getContent()->setBody($body);
    }

    /**
     * Обновление записи в блоге
     */
    private function saveFormBlog(): void
    {
        $entry = new BlogEntry([
            'br_title' => $_POST['ntitle'],
            'br_text' => $_POST['ntext'],
            'br_url' => $_POST['nurl'],
        ]);
        $entry->br_date = Dates::normalToSQL($_POST['ndate']) . ' ' . $_POST['ntime'];
        $entry->br_active = $_POST['nact'] === 'true' ? 1 : 0;
        $entry->setOwner(new User(['us_id' => $this->webUser->getId()]));

        if ($_POST['brid'] > 0) {
            $entry->br_id = (int) $_POST['brid'];
        }

        $this->blogRepository->save($entry);
    }
}
